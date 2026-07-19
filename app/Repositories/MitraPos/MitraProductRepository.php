<?php

namespace App\Repositories\MitraPos;

use App\Interfaces\Repositories\MitraPos\MitraProductRepositoryInterface;
use App\Models\MitraProduct;
use App\Repositories\BaseRepository;

class MitraProductRepository extends BaseRepository implements MitraProductRepositoryInterface
{
    public function __construct(MitraProduct $model)
    {
        $this->model = $model;
    }
}
