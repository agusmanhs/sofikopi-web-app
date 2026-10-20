<?php

namespace App\Services\MitraPos;

use App\Models\MitraMaterial;
use App\Models\MitraProduct;
use App\Repositories\MitraPos\MitraMaterialRepository;
use App\Services\BaseService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MitraMaterialService extends BaseService
{
    public function __construct(MitraMaterialRepository $repository)
    {
        parent::__construct($repository);
    }

    public function forMitra(int $mitraId)
    {
        return MitraMaterial::forMitra($mitraId)->orderBy('name')->get();
    }

    public function paginateForMitra(int $mitraId, int $perPage = 10, ?string $search = null, ?string $category = null)
    {
        $query = MitraMaterial::forMitra($mitraId)->orderBy('name');

        if ($search !== null && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('sku', 'like', $term));
        }

        if ($category !== null && $category !== '') {
            $query->where('category', $category);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Totals over ALL of the mitra's materials (never just one page of the
     * list): active inventory value mirrors MitraMaterial::getStockValueAttribute().
     *
     * @return array{inventory_value: float, inactive_count: int}
     */
    public function inventorySummaryForMitra(int $mitraId): array
    {
        $row = MitraMaterial::forMitra($mitraId)
            ->selectRaw('COALESCE(SUM(CASE WHEN is_active = ? AND netto > 0 THEN current_stock * price_per_pack / netto ELSE 0 END), 0) as inventory_value', [true])
            ->selectRaw('COALESCE(SUM(CASE WHEN is_active = ? THEN 0 ELSE 1 END), 0) as inactive_count', [true])
            ->first();

        return [
            'inventory_value' => (float) $row->inventory_value,
            'inactive_count' => (int) $row->inactive_count,
        ];
    }

    public function categoriesForMitra(int $mitraId)
    {
        return MitraMaterial::forMitra($mitraId)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
    }

    /**
     * Names of products whose recipe uses this material (live products only),
     * used to block deleting a material that a recipe still depends on.
     */
    public function productNamesUsing(int $mitraId, MitraMaterial $material)
    {
        return MitraProduct::forMitra($mitraId)
            ->whereHas('ingredients', fn ($q) => $q->where('mitra_material_id', $material->id))
            ->orderBy('name')
            ->pluck('name');
    }

    /**
     * $sku is scoped to $mitraId in the query itself (never a global sku
     * lookup) — sku is only unique per-mitra, so this guards against ever
     * resolving a different tenant's row even transiently.
     */
    public function findForMitra(int $mitraId, string $sku): MitraMaterial
    {
        $material = MitraMaterial::forMitra($mitraId)->where('sku', $sku)->first();

        if (!$material) {
            throw new NotFoundHttpException('Material tidak ditemukan.');
        }

        return $material;
    }

    public function createForMitra(int $mitraId, array $data): MitraMaterial
    {
        $data['mitra_id'] = $mitraId;

        return $this->repository->create($data);
    }

    public function updateForMitra(int $mitraId, string $sku, array $data): MitraMaterial
    {
        $material = $this->findForMitra($mitraId, $sku);

        // Binding-leak defense: never allow an update to slip across tenants.
        if ((int) $material->mitra_id !== $mitraId) {
            throw new NotFoundHttpException('Material tidak ditemukan.');
        }

        // SKU is the route key and is locked after creation.
        unset($data['sku']);

        $material->update($data);

        return $material;
    }

    public function deleteForMitra(int $mitraId, string $sku): bool
    {
        $material = $this->findForMitra($mitraId, $sku);

        if ((int) $material->mitra_id !== $mitraId) {
            throw new NotFoundHttpException('Material tidak ditemukan.');
        }

        return (bool) $material->delete();
    }
}
