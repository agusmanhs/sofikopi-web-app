<?php

namespace App\Http\Controllers\MitraPos;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\MitraPos\PosCheckoutRequest;
use App\Services\MitraPos\MitraContext;
use App\Services\MitraPos\MitraProductService;
use App\Services\MitraPos\PosTransactionService;

class PosController extends Controller
{
    public function __construct(
        protected MitraProductService $productService,
        protected PosTransactionService $transactionService,
        protected MitraContext $mitraContext
    ) {}

    public function index()
    {
        $products = $this->productService->listActiveForPos($this->mitraContext->id());

        return view('pages.mitra-pos.pos.index', compact('products'));
    }

    /**
     * JSON feed for the product grid AJAX refresh. `current_stock` here is
     * the number of units of this product that can still be made from the
     * current material stock (the binding constraint across its BOM), not
     * a stock field on the product itself — mitra products have no stock
     * of their own, only their raw-material ingredients do.
     */
    public function products()
    {
        $products = $this->productService->listActiveForPos($this->mitraContext->id());

        $payload = $products->map(function ($product) {
            $makeable = null; // null = unconstrained (no ingredients defined yet)

            foreach ($product->ingredients as $ingredient) {
                if ((float) $ingredient->qty <= 0) {
                    continue;
                }

                $possible = floor((float) $ingredient->material->current_stock / (float) $ingredient->qty);
                $makeable = $makeable === null ? $possible : min($makeable, $possible);
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category,
                'price' => (float) $product->sale_price,
                'current_stock' => $makeable,
                'low_stock' => $makeable !== null && $makeable <= 5,
            ];
        });

        return ResponseHelper::success($payload);
    }

    /**
     * Checkout endpoint. Must never 500 on business-rule failures — a
     * caught \Throwable becomes a 422 JSON error instead.
     */
    public function store(PosCheckoutRequest $request)
    {
        $data = $request->validated();

        try {
            $result = $this->transactionService->checkout(
                mitraId: $this->mitraContext->id(),
                userId: auth()->id(),
                items: $data['items'],
                discount: (float) ($data['discount'] ?? 0),
                salesMode: $data['sales_mode'],
                paymentMethod: $data['payment_method'],
            );

            return ResponseHelper::success([
                'transaction' => $result['transaction'],
                'stock_warnings' => $result['stock_warnings'],
            ], 'Transaksi berhasil');
        } catch (\Throwable $e) {
            return ResponseHelper::error('Checkout gagal: ' . $e->getMessage(), 422);
        }
    }
}
