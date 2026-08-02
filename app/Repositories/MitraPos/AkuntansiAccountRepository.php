<?php

namespace App\Repositories\MitraPos;

use App\Interfaces\Repositories\MitraPos\AkuntansiAccountRepositoryInterface;
use App\Models\AkuntansiAccount;
use App\Repositories\BaseRepository;

class AkuntansiAccountRepository extends BaseRepository implements AkuntansiAccountRepositoryInterface
{
    public function __construct(AkuntansiAccount $model)
    {
        $this->model = $model;
    }
}
