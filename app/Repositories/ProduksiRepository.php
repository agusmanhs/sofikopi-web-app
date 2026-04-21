<?php

namespace App\Repositories;

use App\Interfaces\Repositories\ProduksiRepositoryInterface;
use App\Models\Produksi;

class ProduksiRepository extends BaseRepository implements ProduksiRepositoryInterface
{
    public function __construct(Produksi $model)
    {
        parent::__construct($model);
    }

    public function allWithRelations()
    {
        return $this->model->with(['product', 'user'])->orderBy('tanggal', 'desc')->get();
    }
}
