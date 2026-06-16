<?php

namespace App\Repositories;

use App\Models\SalesOrder;
use App\Interfaces\Repositories\SalesOrderRepositoryInterface;

class SalesOrderRepository extends BaseRepository implements SalesOrderRepositoryInterface
{
    public function __construct(SalesOrder $model)
    {
        $this->model = $model;
    }
}
