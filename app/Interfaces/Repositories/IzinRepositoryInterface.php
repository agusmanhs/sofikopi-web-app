<?php

namespace App\Interfaces\Repositories;

interface IzinRepositoryInterface extends BaseRepositoryInterface
{
    public function getByPegawai($pegawaiId);
    public function getPending();
    public function getApproved();
    public function checkOverlap($pegawaiId, string $tglMulai, string $tglSelesai, $excludeId = null);
}
