<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['code', 'regency_code', 'name'];

    public function regency()
    {
        return $this->belongsTo(Regency::class, 'regency_code', 'code');
    }
}
