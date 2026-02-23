<?php

namespace App\Interfaces\Repositories;

interface AbsensiRepositoryInterface extends BaseRepositoryInterface
{
    public function getByPegawaiTanggal($pegawaiId, string $tanggal);
    public function getByPegawaiBulan($pegawaiId, $bulan, $tahun);
    public function getAbsensiHariIni();
    public function getBelumAbsenHariIni();
}
