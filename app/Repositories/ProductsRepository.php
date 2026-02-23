<?php

namespace App\Repositories;

use App\Models\Products;
use App\Interfaces\Repositories\ProductsRepositoryInterface;

class ProductsRepository extends BaseRepository implements ProductsRepositoryInterface
{
    public function __construct(Products $model)
    {
        $this->model = $model;
    }
}