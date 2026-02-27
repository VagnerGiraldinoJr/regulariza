<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SacTicket extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'protocolo',
        'order_id',
        'user_id',
        'atendente_id',
        'assunto',
        'status',
        'prioridade',
        'resolvido_em',
    ];

    protected function casts(): array
    {
        return [
            'resolvido_em' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SacTicket $ticket): void {
            if (! empty($ticket->protocolo)) {
                return;
            }

            // Gera o próximo protocolo SAC no formato SAC-YYYYMMDD-NNNNN.
            $date = now()->format('Ymd');
            $prefix = sprintf('SAC-%s-', $date);

            $lastProtocol = static::withTrashed()
                ->where('protocolo', 'like', $prefix.'%')
                ->latest('id')
                ->value('protocolo');

            $sequence = $lastProtocol ? ((int) substr($lastProtocol, -5)) + 1 : 1;

            $ticket->protocolo = $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function atendente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atendente_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SacMessage::class);
    }

    public function scopeSemAtendente(Builder $query): Builder
    {
        return $query->whereNull('atendente_id');
    }

    public function scopeAbertos(Builder $query): Builder
    {
        return $query->whereIn('status', ['aberto', 'em_atendimento', 'aguardando_cliente']);
    }
}
