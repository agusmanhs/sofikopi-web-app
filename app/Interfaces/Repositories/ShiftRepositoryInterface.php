<?php

namespace App\Interfaces\Repositories;

interface ShiftRepositoryInterface extends BaseRepositoryInterface
{
    public function getByDivisi($divisiId);
    public function getAktif();
}
