<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'invoice_number',
        'sales_order_id',
        'created_by',
        'invoice_date',
        'due_date',
        'subtotal',
        'discount_total',
        'tax_total',
        'grand_total',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'terms',
        'notes',
        'status',
        'payment_method',
        'paid_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getDueStateAttribute(): string
    {
        if ($this->status === 'lunas') {
            return 'paid';
        }

        if (! $this->due_date) {
            return 'ok';
        }

        if ($this->due_date->lt(today())) {
            return 'overdue';
        }

        if ($this->due_date->lte(today()->addDays(3))) {
            return 'near_due';
        }

        return 'ok';
    }

    public function getDueBadgeClassAttribute(): string
    {
        return match ($this->due_state) {
            'paid' => 'success',
            'overdue' => 'danger',
            'near_due' => 'warning',
            default => 'secondary',
        };
    }
}
