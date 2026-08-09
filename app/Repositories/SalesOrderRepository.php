<?php

namespace App\Repositories;

use App\Interfaces\Repositories\SalesOrderRepositoryInterface;
use App\Models\SalesOrder;

class SalesOrderRepository extends BaseRepository implements SalesOrderRepositoryInterface
{
    public function __construct(SalesOrder $model)
    {
        $this->model = $model;
    }

    public function allForIndex()
    {
        return $this->model->with('mitra')->orderBy('order_date', 'desc')->orderBy('id', 'desc')->get();
    }
}
