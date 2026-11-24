<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use LogsActivity;

    protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
        'group',
        'label',
        'type',
        'keterangan',
    ];

    public const GROUP_ABSENSI = 'absensi';

    public const KEY_BATAS_ABSEN_MASUK_JAM = 'batas_absen_masuk_jam';

    public const KEY_BATAS_ABSEN_DIBUKA_JAM = 'batas_absen_dibuka_jam';

    public const KEY_BATAS_ABSEN_PULANG_JAM = 'batas_absen_pulang_jam';

    public const KEY_BATAS_IZIN_JAM = 'batas_izin_jam';

    public function scopeGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public function getCastedValueAttribute()
    {
        return match ($this->type) {
            'integer' => is_null($this->value) ? null : (int) $this->value,
            'float' => is_null($this->value) ? null : (float) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            default => $this->value,
        };
    }
}
