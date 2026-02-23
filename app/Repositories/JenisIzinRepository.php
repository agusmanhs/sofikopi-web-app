<?php

namespace App\Repositories;

use App\Models\JenisIzin;
use App\Interfaces\Repositories\JenisIzinRepositoryInterface;

class JenisIzinRepository extends BaseRepository implements JenisIzinRepositoryInterface
{
    public function __construct(JenisIzin $model)
    {
        $this->model = $model;
    }

    public function getAktif()
    {
        return $this->model->aktif()->get();
    }
}
