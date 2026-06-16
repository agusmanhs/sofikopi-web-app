<?php

namespace App\Repositories;

use App\Models\SalesOrderLog;
use App\Interfaces\Repositories\SalesOrderLogRepositoryInterface;

class SalesOrderLogRepository extends BaseRepository implements SalesOrderLogRepositoryInterface
{
    public function __construct(SalesOrderLog $model)
    {
        $this->model = $model;
    }
}
