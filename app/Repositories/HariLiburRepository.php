<?php

namespace App\Repositories;

use App\Models\HariLibur;
use App\Interfaces\Repositories\HariLiburRepositoryInterface;

class HariLiburRepository extends BaseRepository implements HariLiburRepositoryInterface
{
    public function __construct(HariLibur $model)
    {
        $this->model = $model;
    }
}