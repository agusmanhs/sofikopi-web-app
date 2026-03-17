<?php

namespace App\Repositories;

use App\Interfaces\Repositories\MitraRepositoryInterface;
use App\Models\Mitra;

class MitraRepository extends BaseRepository implements MitraRepositoryInterface
{
    public function __construct(Mitra $model)
    {
        parent::__construct($model);
    }

    public function all()
    {
        return $this->model->with(['category', 'province', 'regency', 'district'])->get();
    }
}
