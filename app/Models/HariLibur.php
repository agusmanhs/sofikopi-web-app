<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HariLibur extends Model
{
    protected $fillable = [
        'tanggal',
        'nama',
        'deskripsi',
        'is_nasional',
        'is_cuti_bersama',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'is_nasional' => 'boolean',
        'is_cuti_bersama' => 'boolean',
    ];
}
