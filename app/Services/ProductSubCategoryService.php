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
        return $this->repository->getByCategory($categoryId);
    }

    public function getAktif()
    {
        return $this->repository->getAktif();
    }
}
