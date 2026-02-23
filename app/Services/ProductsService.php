<?php

namespace App\Services;

use App\Repositories\ProductsRepository;

class ProductsService extends BaseService
{
    public function __construct(ProductsRepository $repository)
    {
        parent::__construct($repository);
    }
}