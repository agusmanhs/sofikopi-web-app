<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    /**
     * Log an activity to the activity_logs table.
     *
     * @param string $action    Action performed (created, updated, deleted, imported, exported, login, etc.)
     * @param string $module    Module name (produksi, kunjungan, products, mitra, absensi, etc.)
     * @param string $description Human-readable description
     * @param Model|null $subject The model subject of the activity
     * @param array|null $properties Extra data (old/new values, etc.)
     */
    protected function logActivity(
        string $action,
        string $module,
        string $description,
        ?Model $subject = null,
        ?array $properties = null
    ): ActivityLog {
        $user = auth()->user();

        return ActivityLog::create([
            'user_id'      => $user?->id,
            'user_name'    => $user?->pegawai?->nama_lengkap ?? $user?->name ?? 'System',
            'action'       => $action,
            'module'       => $module,
            'description'  => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->id,
            'properties'   => $properties,
            'ip_address'   => request()->ip(),
            'user_agent'   => request()->userAgent(),
        ]);
    }
}
