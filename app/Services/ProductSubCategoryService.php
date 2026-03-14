<?php

namespace App\Services;

use App\Interfaces\Repositories\ProductSubCategoryRepositoryInterface;

class ProductSubCategoryService extends BaseService
{
    public function __construct(ProductSubCategoryRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function getByCategory($categoryId)
    {
        return $this->repository->all()->where('product_category_id', $categoryId);
    }
}
