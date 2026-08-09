<?php

namespace App\Interfaces\Repositories;

interface JadwalKerjaRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Ambil pegawai (opsional difilter per divisi) beserta pola jadwal mingguannya.
     */
    public function getPegawaiWithPattern(?int $divisiId = null);

    /**
     * Simpan (buat/perbarui) satu baris pola hari kerja untuk pegawai.
     */
    public function upsertDay(int $pegawaiId, int $dayOfWeek, ?int $shiftId);
}
