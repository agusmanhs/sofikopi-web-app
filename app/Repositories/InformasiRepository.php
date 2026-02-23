<?php

namespace App\Repositories;

use App\Interfaces\Repositories\InformasiRepositoryInterface;
use App\Models\Informasi;

class InformasiRepository extends BaseRepository implements InformasiRepositoryInterface
{
    public function __construct(Informasi $model)
    {
        parent::__construct($model);
    }

    public function getLatest($limit = 5)
    {
        return $this->model->with('user')->latest()->take($limit)->get();
    }

    public function all()
    {
        return $this->model->with('user')->latest()->get();
    }

    public function paginate($perPage = 10)
    {
        return $this->model->with('user')->latest()->paginate($perPage);
    }
}
