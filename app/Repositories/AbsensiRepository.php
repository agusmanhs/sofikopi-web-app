<?php

namespace App\Repositories;

use App\Models\Absensi;
use App\Models\Pegawai;
use App\Interfaces\Repositories\AbsensiRepositoryInterface;

class AbsensiRepository extends BaseRepository implements AbsensiRepositoryInterface
{
    public function __construct(Absensi $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->with('pegawai')->orderBy('tanggal', 'desc')->get();
    }

    public function getByPegawaiTanggal($pegawaiId, string $tanggal)
    {
        return $this->model->where('pegawai_id', $pegawaiId)
            ->whereDate('tanggal', $tanggal)
            ->first();
    }

    public function getByPegawaiBulan($pegawaiId, $bulan, $tahun)
    {
        return $this->model->with('shift')
            ->where('pegawai_id', $pegawaiId)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->orderBy('tanggal', 'desc')
            ->get();
    }

    public function getAbsensiHariIni($tanggal = null)
    {
        $tanggal = $tanggal ?: today()->toDateString();
        return $this->model->with(['pegawai', 'shift'])
            ->whereDate('tanggal', $tanggal)
            ->get();
    }

    public function getBelumAbsenHariIni($tanggal = null)
    {
        $tanggal = $tanggal ?: today()->toDateString();
        $sudahAbsen = $this->model->whereDate('tanggal', $tanggal)
            ->pluck('pegawai_id');

        return Pegawai::aktif()
            ->whereNotIn('id', $sudahAbsen)
            ->with(['divisi', 'kantor'])
            ->get();
    }
    public function paginate($perPage = 10)
    {
        return $this->model->with('pegawai')
            ->orderBy('tanggal', 'desc')
            ->paginate($perPage);
    }
}
