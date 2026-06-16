<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class DeliveryOrder extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'do_number',
        'sales_order_id',
        'assigned_to',
        'assigned_by',
        'assigned_at',
        'delivery_type',
        'status',
        'delivery_date',
        'proof_photo',
        'proof_latitude',
        'proof_longitude',
        'received_by_name',
        'notes',
        'delivered_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'delivery_date' => 'date',
        'delivered_at' => 'datetime',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
