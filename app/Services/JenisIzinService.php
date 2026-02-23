<?php

namespace App\Services;

use App\Repositories\JenisIzinRepository;

class JenisIzinService extends BaseService
{
    public function __construct(JenisIzinRepository $repository)
    {
        parent::__construct($repository);
    }

    public function getAktif()
    {
        return $this->repository->getAktif();
    }
}
