<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'module',
        'description',
        'subject_type',
        'subject_id',
        'old_values',
        'new_values',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'properties' => 'array',
    ];

    protected const ACTION_LABELS = [
        'login' => 'Login',
        'logout' => 'Logout',
        'created' => 'Tambah',
        'updated' => 'Ubah',
        'deleted' => 'Hapus',
        'imported' => 'Impor',
        'exported' => 'Ekspor',
        'absen_masuk' => 'Absen Masuk',
        'absen_pulang' => 'Absen Pulang',
        'koreksi' => 'Koreksi',
        'koreksi_tambah' => 'Tambah Absen',
    ];

    protected const ACTION_COLORS = [
        'login' => 'primary',
        'logout' => 'secondary',
        'created' => 'success',
        'updated' => 'info',
        'deleted' => 'danger',
        'imported' => 'warning',
        'exported' => 'warning',
        'absen_masuk' => 'success',
        'absen_pulang' => 'info',
        'koreksi' => 'warning',
        'koreksi_tambah' => 'warning',
    ];

    public function getActionLabelAttribute(): string
    {
        return self::ACTION_LABELS[$this->action] ?? ucwords(str_replace('_', ' ', (string) $this->action));
    }

    public function getActionColorAttribute(): string
    {
        return self::ACTION_COLORS[$this->action] ?? 'secondary';
    }

    public function getModuleLabelAttribute(): string
    {
        return $this->module ? ucwords(str_replace(['_', '-'], ' ', $this->module)) : '-';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }
}
