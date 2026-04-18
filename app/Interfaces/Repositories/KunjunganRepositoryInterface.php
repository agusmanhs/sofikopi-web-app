<?php

namespace App\Interfaces\Repositories;

interface KunjunganRepositoryInterface extends BaseRepositoryInterface
{
    public function getByUser($userId);
    public function getAllWithRelations($filters = []);
}
