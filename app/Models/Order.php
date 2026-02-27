<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'protocolo',
        'user_id',
        'service_id',
        'lead_id',
        'status',
        'valor',
        'stripe_payment_intent_id',
        'stripe_checkout_session_id',
        'pagamento_status',
        'pago_em',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'pago_em' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function sacTickets(): HasMany
    {
        return $this->hasMany(SacTicket::class);
    }

    public function whatsappLogs(): HasMany
    {
        return $this->hasMany(WhatsappLog::class);
    }

    public function scopePendentes(Builder $query): Builder
    {
        return $query->where('status', 'pendente');
    }

    public function scopePagos(Builder $query): Builder
    {
        return $query->where('pagamento_status', 'pago');
    }
}
