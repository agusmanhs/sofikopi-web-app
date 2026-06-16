<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class SalesOrder extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'order_number',
        'user_id',
        'mitra_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_address',
        'order_date',
        'status',
        'delivery_type',
        'subtotal',
        'discount_total',
        'additional_discount',
        'tax_amount',
        'grand_total',
        'notes',
        'approved_by',
        'approved_at',
        'rejected_reason',
    ];

    protected $casts = [
        'order_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function mitra()
    {
        return $this->belongsTo(Mitra::class, 'mitra_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(SalesOrderItem::class, 'sales_order_id');
    }

    public function deliveryOrder()
    {
        return $this->hasOne(DeliveryOrder::class, 'sales_order_id');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'sales_order_id');
    }

    public function logs()
    {
        return $this->hasMany(SalesOrderLog::class, 'sales_order_id');
    }
}
