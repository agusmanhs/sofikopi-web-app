<?php

namespace App\Services;

use App\Interfaces\Repositories\ProductCategoryRepositoryInterface;

class ProductCategoryService extends BaseService
{
    public function __construct(ProductCategoryRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
