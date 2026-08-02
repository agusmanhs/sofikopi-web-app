<?php

namespace App\Http\Controllers\MitraPos;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\MitraPos\PosCheckoutRequest;
use App\Models\Mitra;
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

    // --- Tenant portal (mitra-pos/pos), mitra context from MitraContext ---

    public function index()
    {
        return $this->renderIndex($this->mitraContext->id(), null);
    }

    public function products()
    {
        return $this->buildProductsPayload($this->mitraContext->id());
    }

    public function store(PosCheckoutRequest $request)
    {
        return $this->handleCheckout($request, $this->mitraContext->id());
    }

    // --- Sofikopi-staff admin (mitra-pos/manage/{mitra}/pos) ---

    public function adminIndex(Mitra $mitra)
    {
        return $this->renderIndex($mitra->id, $mitra);
    }

    public function adminProducts(Mitra $mitra)
    {
        return $this->buildProductsPayload($mitra->id);
    }

    public function adminStore(PosCheckoutRequest $request, Mitra $mitra)
    {
        return $this->handleCheckout($request, $mitra->id);
    }

    private function renderIndex(int $mitraId, ?Mitra $mitra)
    {
        $products = $this->productService->listActiveForPos($mitraId);
        $routes = $this->routesFor($mitra);

        return view('pages.mitra-pos.pos.index', compact('products', 'mitra', 'routes'));
    }

    /**
     * JSON feed for the product grid AJAX refresh. `current_stock` here is
     * the number of units of this product that can still be made from the
     * current material stock (the binding constraint across its BOM), not
     * a stock field on the product itself — mitra products have no stock
     * of their own, only their raw-material ingredients do.
     */
    private function buildProductsPayload(int $mitraId)
    {
        $products = $this->productService->listActiveForPos($mitraId);

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
    private function handleCheckout(PosCheckoutRequest $request, int $mitraId)
    {
        $data = $request->validated();

        try {
            $result = $this->transactionService->checkout(
                mitraId: $mitraId,
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
            return ResponseHelper::error('Checkout gagal: '.$e->getMessage(), 422);
        }
    }

    /**
     * Builds route URLs shared by pos/index.blade.php's inline JS so the
     * same page works for both the tenant portal (no {mitra} param) and the
     * Sofikopi-staff admin picker ({mitra} route param) — same technique as
     * PosTransactionController::routesFor(), but 'receipt' here is a plain
     * string template (client-side .replace()'d), not a closure, since the
     * page builds it once at load time rather than per-row.
     */
    private function routesFor(?Mitra $mitra): array
    {
        return [
            'products' => $mitra
                ? route('mitra-pos-manage.pos.products', $mitra)
                : route('pos.products'),
            'store' => $mitra
                ? route('mitra-pos-manage.pos.store', $mitra)
                : route('pos.store'),
            'receipt' => $mitra
                ? route('mitra-pos-manage.transaction.receipt', [$mitra, '__TRANSACTION_NO__'])
                : route('pos-transaction.receipt', '__TRANSACTION_NO__'),
        ];
    }
}
