<?php

namespace App\Services\MitraPos;

use App\Models\MitraProduct;
use App\Models\MitraProductIngredient;
use App\Repositories\MitraPos\MitraProductRepository;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MitraProductService extends BaseService
{
    public function __construct(MitraProductRepository $repository)
    {
        parent::__construct($repository);
    }

    public function forMitra(int $mitraId)
    {
        return MitraProduct::forMitra($mitraId)->with('ingredients.material')->orderBy('name')->get();
    }

    public function paginateForMitra(int $mitraId, int $perPage = 10)
    {
        return MitraProduct::forMitra($mitraId)->with('ingredients.material')->orderBy('name')->paginate($perPage);
    }

    /**
     * $sku is scoped to $mitraId in the query itself (never a global sku
     * lookup) — sku is only unique per-mitra, so this guards against ever
     * resolving a different tenant's row even transiently.
     */
    public function findForMitra(int $mitraId, string $sku): MitraProduct
    {
        $product = MitraProduct::forMitra($mitraId)->with('ingredients.material')->where('sku', $sku)->first();

        if (!$product) {
            throw new NotFoundHttpException('Produk tidak ditemukan.');
        }

        return $product;
    }

    /**
     * $data['ingredients'] = [['mitra_material_id' => int, 'qty' => float], ...]
     */
    public function createForMitra(int $mitraId, array $data): MitraProduct
    {
        return DB::transaction(function () use ($mitraId, $data) {
            $ingredients = $data['ingredients'] ?? [];
            unset($data['ingredients']);

            $data['mitra_id'] = $mitraId;

            $product = $this->repository->create($data);

            $this->syncIngredients($product, $ingredients);

            return $product->load('ingredients.material');
        });
    }

    public function updateForMitra(int $mitraId, string $sku, array $data): MitraProduct
    {
        return DB::transaction(function () use ($mitraId, $sku, $data) {
            $product = $this->findForMitra($mitraId, $sku);

            // Binding-leak defense: never allow an update to slip across tenants.
            if ((int) $product->mitra_id !== $mitraId) {
                throw new NotFoundHttpException('Produk tidak ditemukan.');
            }

            $ingredients = $data['ingredients'] ?? [];
            unset($data['ingredients']);

            $product->update($data);

            // Replace ingredients wholesale: delete old, insert new.
            $product->ingredients()->delete();
            $this->syncIngredients($product, $ingredients);

            return $product->load('ingredients.material');
        });
    }

    public function deleteForMitra(int $mitraId, string $sku): bool
    {
        $product = $this->findForMitra($mitraId, $sku);

        if ((int) $product->mitra_id !== $mitraId) {
            throw new NotFoundHttpException('Produk tidak ditemukan.');
        }

        return (bool) $product->delete();
    }

    /**
     * Active products for the POS product grid, eager loaded to avoid N+1
     * on the hpp/cogs accessors.
     */
    public function listActiveForPos(int $mitraId)
    {
        return MitraProduct::forMitra($mitraId)
            ->where('status', 'active')
            ->with('ingredients.material')
            ->orderBy('category')
            ->orderBy('name')
            ->get();
    }

    private function syncIngredients(MitraProduct $product, array $ingredients): void
    {
        foreach ($ingredients as $ingredient) {
            MitraProductIngredient::create([
                'mitra_product_id' => $product->id,
                'mitra_material_id' => $ingredient['mitra_material_id'],
                'qty' => $ingredient['qty'],
            ]);
        }
    }
}
