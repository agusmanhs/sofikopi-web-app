<?php

namespace App\Interfaces\Repositories;

interface ProductSubCategoryRepositoryInterface extends BaseRepositoryInterface
{
    public function getByCategory($categoryId);
    public function getAktif();
}
