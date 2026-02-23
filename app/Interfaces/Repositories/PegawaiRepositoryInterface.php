<?php

namespace App\Interfaces\Repositories;

interface PegawaiRepositoryInterface extends BaseRepositoryInterface
{
    public function getAktif();
    public function getByUserId($userId);
    public function getWithRelations($id);
    public function rekapPaginate($bulan, $tahun, $perPage = 10);
}
