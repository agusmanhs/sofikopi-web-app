<?php

namespace App\Services\MitraPos;

use App\Models\Mitra;
use App\Models\MitraMaterial;
use App\Models\MitraProduct;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Repositories\MitraPos\PosTransactionRepository;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PosTransactionService extends BaseService
{
    public function __construct(
        PosTransactionRepository $repository,
        protected MitraStockService $stockService
    ) {
        parent::__construct($repository);
    }

    public function forMitra(int $mitraId, int $perPage = 15)
    {
        return PosTransaction::forMitra($mitraId)
            ->with('user')
            ->orderBy('transacted_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * transaction_no is globally unique, but the mitra_id filter is still
     * applied first (via forMitra) as defense in depth, consistent with
     * every other tenant-scoped lookup in this module.
     */
    public function findForMitra(int $mitraId, string $transactionNo): PosTransaction
    {
        $transaction = PosTransaction::forMitra($mitraId)
            ->with('items.product', 'user')
            ->where('transaction_no', $transactionNo)
            ->first();

        if (!$transaction || (int) $transaction->mitra_id !== $mitraId) {
            throw new NotFoundHttpException('Transaksi tidak ditemukan.');
        }

        return $transaction;
    }

    /**
     * The checkout algorithm. Runs entirely inside a single DB::transaction
     * so that the header, line items, and every material's stock movement
     * commit or roll back together atomically.
     *
     * $items = [['mitra_product_id' => int, 'qty' => float], ...]
     *
     * Stock shortages never block the sale — they are surfaced as
     * `stock_warnings` in the return payload (warn, never block).
     */
    public function checkout(
        int $mitraId,
        int $userId,
        array $items,
        float $discount,
        string $salesMode,
        string $paymentMethod
    ): array {
        return DB::transaction(function () use ($mitraId, $userId, $items, $discount, $salesMode, $paymentMethod) {
            // 1. Load requested products, scoped to this mitra, with BOM eager loaded.
            $productIds = collect($items)->pluck('mitra_product_id')->unique()->all();

            $products = MitraProduct::forMitra($mitraId)
                ->with('ingredients.material')
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');

            // 2. Aggregate BOM: material_id => total required qty across the whole cart.
            $requiredQtyByMaterial = [];
            $lineData = [];
            $subtotal = 0;

            foreach ($items as $line) {
                $product = $products->get($line['mitra_product_id']);
                if (!$product) {
                    throw new NotFoundHttpException("Produk tidak ditemukan (id: {$line['mitra_product_id']}).");
                }

                $qty = (float) $line['qty'];

                foreach ($product->ingredients as $ingredient) {
                    $materialId = $ingredient->mitra_material_id;
                    $requiredQtyByMaterial[$materialId] = ($requiredQtyByMaterial[$materialId] ?? 0)
                        + ((float) $ingredient->qty * $qty);
                }

                // Totals are always computed server-side from the product's own
                // sale_price — never trust client-sent prices.
                $unitPrice = (float) $product->sale_price;
                $lineTotal = $unitPrice * $qty;
                $subtotal += $lineTotal;

                $lineData[] = [
                    'mitra_product_id' => $product->id,
                    'product_name' => $product->name,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    // Snapshot semantics: hpp/cogs are captured at sale time so
                    // future recipe/price changes never retroactively alter old sales.
                    'hpp_snapshot' => (float) $product->hpp,
                    'cogs_snapshot' => (float) $product->cogs,
                    'line_total' => $lineTotal,
                ];
            }

            // 3. Lock the required materials, ordered by id (deadlock-safe).
            $materials = MitraMaterial::forMitra($mitraId)
                ->whereIn('id', array_keys($requiredQtyByMaterial))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // 4. Build stock warnings. Never throws/blocks on short stock.
            $stockWarnings = [];
            foreach ($requiredQtyByMaterial as $materialId => $requiredQty) {
                $material = $materials->get($materialId);
                if ($material && (float) $material->current_stock < $requiredQty) {
                    $stockWarnings[] = [
                        'material_id' => $material->id,
                        'material_name' => $material->name,
                        'required' => $requiredQty,
                        'available' => (float) $material->current_stock,
                    ];
                }
            }

            // 5. Generate transaction_no inside this same lock/transaction window.
            $mitra = Mitra::findOrFail($mitraId);
            $transactionNo = $this->generateTransactionNo($mitraId, $mitra->code);

            $grandTotal = max(0, $subtotal - $discount);

            // 6. Create header + items.
            $transaction = PosTransaction::create([
                'mitra_id' => $mitraId,
                'transaction_no' => $transactionNo,
                'sales_mode' => $salesMode,
                'payment_method' => $paymentMethod,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'grand_total' => $grandTotal,
                'total_hpp' => 0,
                'total_cogs' => 0,
                'status' => 'completed',
                'user_id' => $userId,
                'transacted_at' => now(),
            ]);

            $totalHpp = 0;
            $totalCogs = 0;

            foreach ($lineData as $line) {
                PosTransactionItem::create(array_merge($line, [
                    'pos_transaction_id' => $transaction->id,
                ]));

                $totalHpp += $line['hpp_snapshot'] * $line['qty'];
                $totalCogs += $line['cogs_snapshot'] * $line['qty'];
            }

            // 7. Deduct stock, single writer of cache + ledger, same transaction.
            foreach ($requiredQtyByMaterial as $materialId => $requiredQty) {
                $material = $materials->get($materialId);

                $this->stockService->applyMovement(
                    mitraId: $mitraId,
                    materialId: $materialId,
                    type: 'out',
                    qty: $requiredQty,
                    unitCost: $material?->harga_satuan,
                    notes: "POS sale {$transactionNo}",
                    reference: $transaction,
                    userId: $userId,
                );
            }

            // 8. Roll up header totals.
            $transaction->update([
                'total_hpp' => $totalHpp,
                'total_cogs' => $totalCogs,
            ]);

            // 9. Return transaction + warnings.
            return [
                'transaction' => $transaction->fresh('items'),
                'stock_warnings' => $stockWarnings,
            ];
        });
    }

    /**
     * POS/{mitra_code}/{Ymd}/{seq4}. Generated INSIDE the caller's
     * transaction, locking the latest same-day-same-mitra row before
     * computing the next sequence, to avoid the unlocked-lookup race
     * SalesOrderService::generateOrderNumber() has. The unique index on
     * transaction_no is the backstop, not the primary mechanism.
     */
    private function generateTransactionNo(int $mitraId, string $mitraCode): string
    {
        $ymd = now()->format('Ymd');
        $prefix = "POS/{$mitraCode}/{$ymd}/";

        $latest = PosTransaction::forMitra($mitraId)
            ->where('transaction_no', 'like', $prefix . '%')
            ->orderBy('transaction_no', 'desc')
            ->lockForUpdate()
            ->first();

        $nextSeq = 1;
        if ($latest && preg_match('/(\d{4})$/', $latest->transaction_no, $matches)) {
            $nextSeq = intval($matches[1]) + 1;
        }

        return $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }
}
