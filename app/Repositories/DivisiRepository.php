<?php

namespace App\Repositories;

use App\Models\Divisi;
use App\Interfaces\Repositories\DivisiRepositoryInterface;

class DivisiRepository extends BaseRepository implements DivisiRepositoryInterface
{
    public function __construct(Divisi $model)
    {
        $this->model = $model;
    }

    public function getAktif()
    {
        return $this->model->aktif()->get();
    }
}
