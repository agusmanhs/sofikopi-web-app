<?php

namespace App\Repositories;

use App\Models\Izin;
use App\Interfaces\Repositories\IzinRepositoryInterface;

class IzinRepository extends BaseRepository implements IzinRepositoryInterface
{
    public function __construct(Izin $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->with(['pegawai', 'jenisIzin', 'approver'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getByPegawai($pegawaiId)
    {
        return $this->model->where('pegawai_id', $pegawaiId)
            ->with(['jenisIzin', 'approver'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getPending()
    {
        return $this->model->pending()
            ->with(['pegawai', 'jenisIzin'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getApproved()
    {
        return $this->model->approved()
            ->with(['pegawai', 'jenisIzin', 'approver'])
            ->orderBy('approved_at', 'desc')
            ->get();
    }

    /**
     * Check apakah ada izin yang overlap dengan tanggal yang diajukan
     */
    public function checkOverlap($pegawaiId, string $tglMulai, string $tglSelesai, $excludeId = null)
    {
        $query = $this->model->where('pegawai_id', $pegawaiId)
            ->where('status_approval', '!=', Izin::STATUS_REJECTED)
            ->where(function ($q) use ($tglMulai, $tglSelesai) {
                $q->whereBetween('tgl_mulai', [$tglMulai, $tglSelesai])
                    ->orWhereBetween('tgl_selesai', [$tglMulai, $tglSelesai])
                    ->orWhere(function ($q2) use ($tglMulai, $tglSelesai) {
                        $q2->where('tgl_mulai', '<=', $tglMulai)
                           ->where('tgl_selesai', '>=', $tglSelesai);
                    });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function paginate($perPage = 10)
    {
        return $this->model->with(['pegawai', 'jenisIzin', 'approver'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getStatistik()
    {
        return [
            'pending' => $this->model->where('status_approval', Izin::STATUS_PENDING)->count(),
            'approved' => $this->model->where('status_approval', Izin::STATUS_APPROVED)->count(),
            'rejected' => $this->model->where('status_approval', Izin::STATUS_REJECTED)->count(),
            'total' => $this->model->count(),
        ];
    }
}
