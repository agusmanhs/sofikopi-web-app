<?php

namespace App\Repositories;

use App\Interfaces\Repositories\SettingRepositoryInterface;
use App\Models\Setting;

class SettingRepository extends BaseRepository implements SettingRepositoryInterface
{
    public function __construct(Setting $model)
    {
        $this->model = $model;
    }

    public function getByGroup(string $group)
    {
        return $this->model->newQuery()
            ->where('group', $group)
            ->orderBy('id')
            ->get();
    }

    public function findByKey(string $key)
    {
        return $this->model->newQuery()->where('key', $key)->first();
    }

    public function setByKey(string $key, $value)
    {
        $setting = $this->findByKey($key);

        if (! $setting) {
            throw new \Exception("Setting with key '{$key}' not found. Cannot update non-existent setting.");
        }

        $setting->update(['value' => $value]);

        return $setting;
    }
}
