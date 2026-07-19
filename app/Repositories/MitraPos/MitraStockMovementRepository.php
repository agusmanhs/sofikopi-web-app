<?php

namespace App\Repositories\MitraPos;

use App\Interfaces\Repositories\MitraPos\MitraStockMovementRepositoryInterface;
use App\Models\MitraStockMovement;
use App\Repositories\BaseRepository;

class MitraStockMovementRepository extends BaseRepository implements MitraStockMovementRepositoryInterface
{
    public function __construct(MitraStockMovement $model)
    {
        $this->model = $model;
    }
}
