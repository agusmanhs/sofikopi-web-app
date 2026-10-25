<?php

namespace App\Models;

use App\Services\RoleAccessCache;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    protected static function booted(): void
    {
        $flush = fn () => app(RoleAccessCache::class)->flush();

        static::saved($flush);
        static::deleted($flush);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'role_menu')->withPivot('can_create', 'can_read', 'can_update', 'can_delete');
    }
}
