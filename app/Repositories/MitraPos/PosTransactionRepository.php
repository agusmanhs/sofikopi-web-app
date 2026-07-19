<?php

namespace App\Repositories\MitraPos;

use App\Interfaces\Repositories\MitraPos\PosTransactionRepositoryInterface;
use App\Models\PosTransaction;
use App\Repositories\BaseRepository;

class PosTransactionRepository extends BaseRepository implements PosTransactionRepositoryInterface
{
    public function __construct(PosTransaction $model)
    {
        $this->model = $model;
    }
}
