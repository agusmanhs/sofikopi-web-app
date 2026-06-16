<?php

namespace App\Repositories;

use App\Models\DeliveryOrder;
use App\Interfaces\Repositories\DeliveryOrderRepositoryInterface;

class DeliveryOrderRepository extends BaseRepository implements DeliveryOrderRepositoryInterface
{
    public function __construct(DeliveryOrder $model)
    {
        $this->model = $model;
    }

    public function getByAssignedUser($userId)
    {
        return $this->model->where('assigned_to', $userId)->get();
    }
}
