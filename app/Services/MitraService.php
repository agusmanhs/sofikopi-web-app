<?php

namespace App\Services;

use App\Interfaces\Repositories\MitraRepositoryInterface;

class MitraService extends BaseService
{
    public function __construct(MitraRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
