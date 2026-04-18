<?php

namespace App\Repositories;

use App\Interfaces\Repositories\KunjunganRepositoryInterface;
use App\Models\Kunjungan;

class KunjunganRepository extends BaseRepository implements KunjunganRepositoryInterface
{
    public function __construct(Kunjungan $model)
    {
        parent::__construct($model);
    }

    /**
     * Get kunjungan by user (untuk history pribadi)
     */
    public function getByUser($userId)
    {
        return $this->model->with(['mitra', 'user'])
            ->where('user_id', $userId)
            ->orderByDesc('tanggal_kunjungan')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get all with relations and optional filters (untuk admin)
     */
    public function getAllWithRelations($filters = [])
    {
        $query = $this->model->with(['mitra', 'user.pegawai']);

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['mitra_id'])) {
            $query->where('mitra_id', $filters['mitra_id']);
        }

        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->whereBetween('tanggal_kunjungan', [$filters['date_from'], $filters['date_to']]);
        }

        return $query->orderByDesc('tanggal_kunjungan')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Find with full relations
     */
    public function findWithRelations($id)
    {
        return $this->model->with(['mitra', 'user.pegawai'])->findOrFail($id);
    }
}
