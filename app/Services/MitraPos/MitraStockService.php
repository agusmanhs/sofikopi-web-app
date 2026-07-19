<?php

namespace App\Services\MitraPos;

use App\Models\MitraMaterial;
use App\Models\MitraStockMovement;
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

        if (!$material) {
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
}
