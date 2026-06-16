<?php

namespace App\Interfaces\Repositories;

interface DeliveryOrderRepositoryInterface
{
    public function getByAssignedUser($userId);
}
