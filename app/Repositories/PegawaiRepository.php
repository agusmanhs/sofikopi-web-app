<?php

namespace App\Repositories;

use App\Models\Pegawai;
use App\Interfaces\Repositories\PegawaiRepositoryInterface;

class PegawaiRepository extends BaseRepository implements PegawaiRepositoryInterface
{
    public function __construct(Pegawai $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->with(['user', 'divisi', 'shift', 'kantor'])->get();
    }

    public function getAktif()
    {
        return $this->model->aktif()->with(['user', 'divisi', 'shift', 'kantor'])->get();
    }

    public function getByUserId($userId)
    {
        return $this->model->where('user_id', $userId)->with(['divisi', 'shift', 'kantor'])->first();
    }

    public function getWithRelations($id)
    {
        return $this->model->with(['user', 'divisi', 'shift', 'kantor', 'lokasiAbsen'])->findOrFail($id);
    }

    public function paginate($filters = [], $perPage = 10)
    {
        $query = $this->applyFilters($filters);
        return $query->latest()->paginate($perPage);
    }

    public function getFiltered($filters = [])
    {
        $query = $this->applyFilters($filters);
        return $query->latest()->get();
    }

    protected function applyFilters($filters = [])
    {
        $query = $this->model->with(['user', 'divisi', 'shift', 'kantor']);

        if (!empty($filters['divisi_id'])) {
            $query->where('divisi_id', $filters['divisi_id']);
        }

        return $query;
    }

    public function rekapAll($bulan, $tahun)
    {
        return $this->model->aktif()
            ->with(['divisi', 'shift', 'absensis' => function ($q) use ($bulan, $tahun) {
                $q->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
            }])
            ->get();
    }

    public function rekapPaginate($bulan, $tahun, $perPage = 10)
    {
        return $this->model->aktif()
            ->with(['divisi', 'shift', 'absensis' => function ($q) use ($bulan, $tahun) {
                $q->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
            }])
            ->paginate($perPage);
    }
}
