<?php

namespace App\Services;

use App\Repositories\KantorRepository;

class KantorService extends BaseService
{
    public function __construct(KantorRepository $repository)
    {
        parent::__construct($repository);
    }

    public function getAktif()
    {
        return $this->repository->getAktif();
    }
}
