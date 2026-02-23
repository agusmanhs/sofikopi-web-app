<?php

namespace App\Services;

use App\Interfaces\Repositories\InformasiRepositoryInterface;

class InformasiService extends BaseService
{
    public function __construct(InformasiRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function getLatest($limit = 5)
    {
        return $this->repository->getLatest($limit);
    }
}
