<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    use LogsActivity;
    protected $fillable = [
        'cover',
        'name',
        'price',
        'quantity',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
