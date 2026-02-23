<?php

namespace App\Repositories;

use App\Models\Kantor;
use App\Interfaces\Repositories\KantorRepositoryInterface;

class KantorRepository extends BaseRepository implements KantorRepositoryInterface
{
    public function __construct(Kantor $model)
    {
        $this->model = $model;
    }

    public function getAktif()
    {
        return $this->model->aktif()->get();
    }
}
