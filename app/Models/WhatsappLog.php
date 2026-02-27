<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'telefone',
        'evento',
        'mensagem',
        'status',
        'zapi_response',
        'enviado_em',
    ];

    protected function casts(): array
    {
        return [
            'zapi_response' => 'array',
            'enviado_em' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
