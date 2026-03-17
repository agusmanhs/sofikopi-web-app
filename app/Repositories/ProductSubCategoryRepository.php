<?php

namespace App\Repositories;

use App\Interfaces\Repositories\ProductSubCategoryRepositoryInterface;
use App\Models\ProductSubCategory;

class ProductSubCategoryRepository extends BaseRepository implements ProductSubCategoryRepositoryInterface
{
    public function __construct(ProductSubCategory $model)
    {
        parent::__construct($model);
    }

    public function all()
    {
        return $this->model->with('category')->get();
    }

    public function getByCategory($categoryId)
    {
        return $this->model->where('product_category_id', $categoryId)->get();
    }

    public function getAktif()
    {
        return $this->model->aktif()->with('category')->get();
    }
}
