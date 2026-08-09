<?php

namespace App\Repositories;

use App\Interfaces\Repositories\JadwalKerjaRepositoryInterface;
use App\Models\JadwalKerja;
use App\Models\Pegawai;

class JadwalKerjaRepository extends BaseRepository implements JadwalKerjaRepositoryInterface
{
    public function __construct(JadwalKerja $model)
    {
        $this->model = $model;
    }

    public function getPegawaiWithPattern(?int $divisiId = null)
    {
        $query = Pegawai::with(['divisi', 'jadwalKerjas.shift'])
            ->aktif()
            ->orderBy('nama_lengkap');

        if ($divisiId) {
            $query->where('divisi_id', $divisiId);
        }

        return $query->get();
    }

    public function upsertDay(int $pegawaiId, int $dayOfWeek, ?int $shiftId)
    {
        return $this->model->updateOrCreate(
            ['pegawai_id' => $pegawaiId, 'day_of_week' => $dayOfWeek],
            ['shift_id' => $shiftId]
        );
    }
}
