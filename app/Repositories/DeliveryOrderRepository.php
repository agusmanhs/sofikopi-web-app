<?php

namespace App\Repositories;

use App\Interfaces\Repositories\DeliveryOrderRepositoryInterface;
use App\Models\DeliveryOrder;

class DeliveryOrderRepository extends BaseRepository implements DeliveryOrderRepositoryInterface
{
    public function __construct(DeliveryOrder $model)
    {
        $this->model = $model;
    }

    public function getByAssignedUser($userId)
    {
        return $this->model->with('salesOrder.mitra')->where('assigned_to', $userId)->get();
    }

    public function allForIndex()
    {
        return $this->model->with('salesOrder.mitra')->orderBy('created_at', 'desc')->orderBy('id', 'desc')->get();
    }
}
