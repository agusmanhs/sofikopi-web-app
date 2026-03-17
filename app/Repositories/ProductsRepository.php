<?php

namespace App\Repositories;

use App\Interfaces\Repositories\ProductsRepositoryInterface;
use App\Models\Products;

class ProductsRepository extends BaseRepository implements ProductsRepositoryInterface
{
    public function __construct(Products $model)
    {
        parent::__construct($model);
    }

    public function all()
    {
        return $this->model->with(['subCategory.category'])->get();
    }
}