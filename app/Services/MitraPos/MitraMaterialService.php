<?php

namespace App\Services\MitraPos;

use App\Models\MitraMaterial;
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

    public function paginateForMitra(int $mitraId, int $perPage = 10)
    {
        return MitraMaterial::forMitra($mitraId)->orderBy('name')->paginate($perPage);
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
