<?php

namespace App\Repositories;

use App\Models\Shift;
use App\Interfaces\Repositories\ShiftRepositoryInterface;

class ShiftRepository extends BaseRepository implements ShiftRepositoryInterface
{
    public function __construct(Shift $model)
    {
        $this->model = $model;
    }

    public function getByDivisi($divisiId)
    {
        return $this->model->where('divisi_id', $divisiId)->aktif()->get();
    }

    public function getAktif()
    {
        return $this->model->aktif()->get();
    }

    public function paginateWithRelations($filters = [], $perPage = 10)
    {
        $query = $this->applyFilters($filters);
        return $query->latest()->paginate($perPage);
    }

    public function getAllWithRelations($filters = [])
    {
        $query = $this->applyFilters($filters);
        return $query->latest()->get();
    }

    protected function applyFilters($filters = [])
    {
        $query = $this->model->with('divisi');

        if (!empty($filters['divisi_id'])) {
            $query->where('divisi_id', $filters['divisi_id']);
        }

        return $query;
    }
}
