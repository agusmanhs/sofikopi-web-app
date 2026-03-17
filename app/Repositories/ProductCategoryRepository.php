<?php

namespace App\Repositories;

use App\Interfaces\Repositories\ProductCategoryRepositoryInterface;
use App\Models\ProductCategory;

class ProductCategoryRepository extends BaseRepository implements ProductCategoryRepositoryInterface
{
    public function __construct(ProductCategory $model)
    {
        parent::__construct($model);
    }

    public function getAktif()
    {
        return $this->model->aktif()->get();
    }
}
