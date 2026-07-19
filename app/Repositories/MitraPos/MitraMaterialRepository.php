<?php

namespace App\Repositories\MitraPos;

use App\Interfaces\Repositories\MitraPos\MitraMaterialRepositoryInterface;
use App\Models\MitraMaterial;
use App\Repositories\BaseRepository;

class MitraMaterialRepository extends BaseRepository implements MitraMaterialRepositoryInterface
{
    public function __construct(MitraMaterial $model)
    {
        $this->model = $model;
    }
}
