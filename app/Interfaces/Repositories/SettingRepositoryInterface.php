<?php

namespace App\Interfaces\Repositories;

interface SettingRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Ambil semua setting dalam satu group, terurut berdasarkan id.
     */
    public function getByGroup(string $group);

    /**
     * Ambil satu setting berdasarkan key. Null bila tidak ada.
     */
    public function findByKey(string $key);

    /**
     * Simpan nilai satu setting berdasarkan key (idempotent).
     */
    public function setByKey(string $key, $value);
}
