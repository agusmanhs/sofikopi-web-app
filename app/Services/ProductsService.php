<?php

namespace App\Services;

use App\Interfaces\Repositories\ProductsRepositoryInterface;

class ProductsService extends BaseService
{
    public function __construct(ProductsRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}