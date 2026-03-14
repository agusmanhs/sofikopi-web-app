<?php

namespace App\Services;

use App\Interfaces\Repositories\MitraCategoryRepositoryInterface;

class MitraCategoryService extends BaseService
{
    public function __construct(MitraCategoryRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
