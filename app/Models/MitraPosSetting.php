<?php

namespace App\Models;

use App\Traits\BelongsToMitra;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MitraPosSetting extends Model
{
    use BelongsToMitra;

    protected $fillable = [
        'mitra_id',
        'monthly_revenue_target',
        'receipt_footer',
        'service_charge_percent',
        'tax_percent',
        'qris_fee_percent',
        'transfer_fee_percent',
        'edc_fee_percent',
    ];

    protected $casts = [
        'monthly_revenue_target' => 'decimal:2',
        'service_charge_percent' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'qris_fee_percent' => 'decimal:2',
        'transfer_fee_percent' => 'decimal:2',
        'edc_fee_percent' => 'decimal:2',
    ];

    /**
     * The admin-fee percent for a given payment method, used at checkout to
     * compute `admin_fee` — deducted by the payment provider, never added to
     * the customer's bill (see pos_transactions.admin_fee).
     */
    public function feePercentFor(string $paymentMethod): float
    {
        return match ($paymentMethod) {
            'qris' => (float) $this->qris_fee_percent,
            'transfer' => (float) $this->transfer_fee_percent,
            'edc' => (float) $this->edc_fee_percent,
            default => 0.0, // cash has no provider fee
        };
    }

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class);
    }
}
