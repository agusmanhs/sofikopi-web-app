<?php

namespace App\Services\MitraPos;

use App\Models\Mitra;
use App\Models\MitraMaterial;
use App\Models\MitraStockMovement;
use App\Models\MitraStockOpname;
use App\Models\MitraStockOpnameItem;
use App\Repositories\MitraPos\MitraStockMovementRepository;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MitraStockService extends BaseService
{
    public function __construct(MitraStockMovementRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Single writer of both the stock ledger (MitraStockMovement) and the
     * cached balance (MitraMaterial::current_stock).
     *
     * IMPORTANT: this method does NOT open its own DB::transaction. It is
     * designed to run inside a transaction already owned by the caller
     * (e.g. PosTransactionService::checkout()) so that the whole checkout —
     * header + items + every material's stock movement — commits or rolls
     * back atomically as one unit. Callers that invoke this as a standalone
     * action (not part of a larger transaction) MUST wrap the call in their
     * own DB::transaction (see adjustStock() below for that pattern).
     *
     * type: 'in' adds qty, 'out' subtracts qty, 'adjustment' adds a signed
     * delta (qty can be negative). Stock is allowed to go negative for
     * 'out' movements — warn, never block (per plan risk register).
     */
    public function applyMovement(
        int $mitraId,
        int $materialId,
        string $type,
        float $qty,
        ?float $unitCost = null,
        ?string $notes = null,
        ?Model $reference = null,
        ?int $userId = null
    ): MitraStockMovement {
        /** @var MitraMaterial|null $material */
        $material = MitraMaterial::forMitra($mitraId)
            ->where('id', $materialId)
            ->lockForUpdate()
            ->first();

        if (! $material) {
            throw new NotFoundHttpException('Material tidak ditemukan.');
        }

        $currentStock = (float) $material->current_stock;

        $balanceAfter = match ($type) {
            'in' => $currentStock + $qty,
            'out' => $currentStock - $qty,
            'adjustment' => $currentStock + $qty, // qty already signed
            default => throw new \InvalidArgumentException("Unknown stock movement type: {$type}"),
        };

        $movement = MitraStockMovement::create([
            'mitra_id' => $mitraId,
            'mitra_material_id' => $material->id,
            'type' => $type,
            'qty' => $qty,
            'unit_cost' => $unitCost,
            'balance_after' => $balanceAfter,
            'reference_type' => $reference ? $reference->getMorphClass() : null,
            'reference_id' => $reference?->getKey(),
            'notes' => $notes,
            'user_id' => $userId,
        ]);

        $material->current_stock = $balanceAfter;
        $material->save();

        return $movement;
    }

    /**
     * Paginated stock movement ledger for a mitra, filterable by material/
     * type/date range — the "riwayat mutasi stok" screen (sheet PERSEDIAAN's
     * stok masuk/keluar columns, but browsable per-transaction instead of a
     * single monthly total).
     */
    public function movementsForMitra(int $mitraId, array $filters = [], int $perPage = 20)
    {
        $query = MitraStockMovement::forMitra($mitraId)
            ->with(['material', 'user'])
            ->orderByDesc('created_at');

        if (! empty($filters['material_id'])) {
            $query->where('mitra_material_id', $filters['material_id']);
        }
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return $query->paginate($perPage)->appends($filters);
    }

    /**
     * Manual "stock adjust" admin action (not part of a checkout), so it
     * owns its own transaction boundary around applyMovement().
     */
    public function adjustStock(int $mitraId, int $materialId, float $signedDelta, string $notes, int $userId): MitraStockMovement
    {
        return DB::transaction(function () use ($mitraId, $materialId, $signedDelta, $notes, $userId) {
            return $this->applyMovement(
                mitraId: $mitraId,
                materialId: $materialId,
                type: 'adjustment',
                qty: $signedDelta,
                notes: $notes,
                userId: $userId,
            );
        });
    }

    /**
     * Physical stock count (sheet PERSEDIAAN): records system vs physical
     * qty per material and auto-corrects the cached balance via an
     * 'adjustment' movement wherever they differ. $counts is
     * [['mitra_material_id' => int, 'physical_qty' => float], ...].
     *
     * Materials are locked in id order (same deadlock-safe pattern as
     * PosTransactionService::checkout()) so a concurrent sale/opname on the
     * same material can't race with this one.
     */
    public function performOpname(int $mitraId, int $userId, array $counts, ?string $notes = null): MitraStockOpname
    {
        return DB::transaction(function () use ($mitraId, $userId, $counts, $notes) {
            $materialIds = collect($counts)->pluck('mitra_material_id')->unique()->all();

            $materials = MitraMaterial::forMitra($mitraId)
                ->whereIn('id', $materialIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $mitra = Mitra::findOrFail($mitraId);
            $opnameNo = $this->generateOpnameNo($mitraId, $mitra->code);

            $opname = MitraStockOpname::create([
                'mitra_id' => $mitraId,
                'opname_no' => $opnameNo,
                'opname_date' => now()->toDateString(),
                'user_id' => $userId,
                'notes' => $notes,
            ]);

            foreach ($counts as $count) {
                $material = $materials->get((int) $count['mitra_material_id']);
                if (! $material) {
                    throw new NotFoundHttpException("Material tidak ditemukan (id: {$count['mitra_material_id']}).");
                }

                $systemQty = (float) $material->current_stock;
                $physicalQty = (float) $count['physical_qty'];
                $difference = $physicalQty - $systemQty;
                $unitCost = (float) $material->harga_satuan;

                MitraStockOpnameItem::create([
                    'mitra_stock_opname_id' => $opname->id,
                    'mitra_material_id' => $material->id,
                    'system_qty' => $systemQty,
                    'physical_qty' => $physicalQty,
                    'difference' => $difference,
                    'unit_cost' => $unitCost,
                ]);

                // Skip a no-op adjustment movement when the count matches —
                // avoids cluttering the ledger with zero-qty rows.
                if (abs($difference) > 0.0001) {
                    $this->applyMovement(
                        mitraId: $mitraId,
                        materialId: $material->id,
                        type: 'adjustment',
                        qty: $difference,
                        unitCost: $unitCost,
                        notes: "Stock opname {$opnameNo}",
                        reference: $opname,
                        userId: $userId,
                    );
                }
            }

            return $opname->fresh(['items.material']);
        });
    }

    /**
     * OPN/{mitra_code}/{Ymd}/{seq4}. Same locked-lookup pattern as
     * PosTransactionService::generateTransactionNo() — locks the latest
     * same-day-same-mitra row before computing the next sequence.
     */
    private function generateOpnameNo(int $mitraId, string $mitraCode): string
    {
        $ymd = now()->format('Ymd');
        $prefix = "OPN/{$mitraCode}/{$ymd}/";

        $latest = MitraStockOpname::forMitra($mitraId)
            ->where('opname_no', 'like', $prefix.'%')
            ->orderBy('opname_no', 'desc')
            ->lockForUpdate()
            ->first();

        $nextSeq = 1;
        if ($latest && preg_match('/(\d{4})$/', $latest->opname_no, $matches)) {
            $nextSeq = intval($matches[1]) + 1;
        }

        return $prefix.str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }
}
