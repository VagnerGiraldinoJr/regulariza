<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'analyst_id',
        'debt_amount',
        'fee_amount',
        'entry_percentage',
        'entry_amount',
        'installments_count',
        'status',
        'payment_provider',
        'asaas_customer_id',
        'document_path',
        'accepted_at',
        'portal_access_sent_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'debt_amount' => 'decimal:2',
            'fee_amount' => 'decimal:2',
            'entry_percentage' => 'decimal:2',
            'entry_amount' => 'decimal:2',
            'accepted_at' => 'datetime',
            'portal_access_sent_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function analyst(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analyst_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(ContractInstallment::class);
    }
}
