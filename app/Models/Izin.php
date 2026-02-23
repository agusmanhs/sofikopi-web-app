<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Traits\LogsActivity;

class Izin extends Model
{
    use LogsActivity;

    protected $table = 'izins';

    protected $fillable = [
        'pegawai_id',
        'jenis_izin_id',
        'tgl_mulai',
        'tgl_selesai',
        'jumlah_hari',
        'alasan',
        'file_surat',
        'status_approval',
        'approved_by',
        'approved_at',
        'catatan_admin',
    ];

    protected $casts = [
        'tgl_mulai' => 'date',
        'tgl_selesai' => 'date',
        'jumlah_hari' => 'integer',
        'approved_at' => 'datetime',
    ];

    /**
     * Status approval constants
     */
    const STATUS_PENDING = 'Pending';
    const STATUS_APPROVED = 'Approved';
    const STATUS_REJECTED = 'Rejected';

    /**
     * Pegawai yang mengajukan izin
     */
    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class);
    }

    /**
     * Jenis izin
     */
    public function jenisIzin()
    {
        return $this->belongsTo(JenisIzin::class);
    }

    /**
     * User yang approve
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * URL file surat
     */
    public function getFileSuratUrlAttribute()
    {
        if ($this->file_surat) {
            return Storage::url($this->file_surat);
        }
        return null;
    }

    /**
     * Scope untuk filter status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status_approval', $status);
    }

    /**
     * Scope untuk izin pending
     */
    public function scopePending($query)
    {
        return $query->where('status_approval', self::STATUS_PENDING);
    }

    /**
     * Scope untuk izin approved
     */
    public function scopeApproved($query)
    {
        return $query->where('status_approval', self::STATUS_APPROVED);
    }

    /**
     * Check apakah izin sudah di-approve
     */
    public function getIsApprovedAttribute()
    {
        return $this->status_approval === self::STATUS_APPROVED;
    }

    /**
     * Check apakah izin masih pending
     */
    public function getIsPendingAttribute()
    {
        return $this->status_approval === self::STATUS_PENDING;
    }

    /**
     * Boot method untuk auto-calculate jumlah_hari
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->tgl_mulai && $model->tgl_selesai) {
                $model->jumlah_hari = $model->tgl_mulai->diffInDays($model->tgl_selesai) + 1;
            }
        });
    }
}
