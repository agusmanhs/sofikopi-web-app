<?php

namespace App\Services;

use App\Repositories\DivisiRepository;

class DivisiService extends BaseService
{
    public function __construct(DivisiRepository $repository)
    {
        parent::__construct($repository);
    }

    public function getAktif()
    {
        return $this->repository->getAktif();
    }
}
