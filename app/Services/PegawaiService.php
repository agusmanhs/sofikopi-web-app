<?php

namespace App\Services;

use App\Models\LokasiAbsenPegawai;
use App\Repositories\PegawaiRepository;

class PegawaiService extends BaseService
{
    public function __construct(PegawaiRepository $repository)
    {
        parent::__construct($repository);
    }

    public function getAllPegawai($filters = [])
    {
        return $this->repository->getFiltered($filters);
    }

    public function getAktif()
    {
        return $this->repository->getAktif();
    }

    public function getByUserId($userId)
    {
        return $this->repository->getByUserId($userId);
    }

    public function getWithRelations($id)
    {
        return $this->repository->getWithRelations($id);
    }

    /**
     * Assign lokasi absensi untuk pegawai
     */
    public function assignLokasiAbsen($pegawaiId, array $kantorIds)
    {
        // Hapus semua lokasi lama
        LokasiAbsenPegawai::where('pegawai_id', $pegawaiId)->delete();

        // Insert lokasi baru
        foreach ($kantorIds as $kantorId) {
            LokasiAbsenPegawai::create([
                'pegawai_id' => $pegawaiId,
                'kantor_id' => $kantorId,
                'is_aktif' => true,
            ]);
        }

        return true;
    }

    /**
     * Toggle status lokasi absensi
     */
    public function toggleLokasiAbsen($pegawaiId, $kantorId)
    {
        $lokasi = LokasiAbsenPegawai::where('pegawai_id', $pegawaiId)
            ->where('kantor_id', $kantorId)
            ->first();

        if ($lokasi) {
            $lokasi->update(['is_aktif' => !$lokasi->is_aktif]);
            return $lokasi;
        }

        return null;
    }

    public function rekapAll($bulan, $tahun)
    {
        return $this->repository->rekapAll($bulan, $tahun);
    }

    public function rekapPaginate($bulan, $tahun, $perPage = 10)
    {
        return $this->repository->rekapPaginate($bulan, $tahun, $perPage);
    }
}
