<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['code', 'name'];

    public function regencies()
    {
        return $this->hasMany(Regency::class, 'province_code', 'code');
    }
}
