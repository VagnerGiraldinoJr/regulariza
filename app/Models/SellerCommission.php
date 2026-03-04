<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'seller_id',
        'source_type',
        'source_id',
        'base_amount',
        'rate',
        'commission_amount',
        'status',
        'available_at',
        'payout_requested_at',
        'paid_at',
        'asaas_transfer_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'base_amount' => 'decimal:2',
            'rate' => 'decimal:4',
            'commission_amount' => 'decimal:2',
            'available_at' => 'datetime',
            'payout_requested_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
