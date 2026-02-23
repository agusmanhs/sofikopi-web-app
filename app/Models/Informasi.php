<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\LogsActivity;

class Informasi extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'gambar',
        'judul',
        'isi',
    ];

    /**
     * Custom description for activity log
     */
    protected static function getLogDescription(string $action, Model $model): string
    {
        $identifier = $model->getAttribute('judul') ?? $model->getKey();
        
        return match ($action) {
            'created' => "Informasi '{$identifier}' telah diterbitkan",
            'updated' => "Informasi '{$identifier}' telah diperbarui",
            'deleted' => "Informasi '{$identifier}' telah dihapus",
            default => "Informasi '{$identifier}' - {$action}",
        };
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getGambarUrlAttribute()
    {
        if ($this->gambar) {
            return \Illuminate\Support\Facades\Storage::url($this->gambar);
        }
        return asset('assets/img/elements/18.png'); // Default placeholder
    }
}
