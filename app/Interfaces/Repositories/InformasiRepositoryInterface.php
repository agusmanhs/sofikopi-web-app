<?php

namespace App\Interfaces\Repositories;

interface InformasiRepositoryInterface extends BaseRepositoryInterface
{
    public function getLatest($limit = 5);
}
