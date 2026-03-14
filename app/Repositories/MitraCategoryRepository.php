<?php

namespace App\Repositories;

use App\Interfaces\Repositories\MitraCategoryRepositoryInterface;
use App\Models\MitraCategory;

class MitraCategoryRepository extends BaseRepository implements MitraCategoryRepositoryInterface
{
    public function __construct(MitraCategory $model)
    {
        parent::__construct($model);
    }
}
