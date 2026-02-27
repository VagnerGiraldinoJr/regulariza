<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SacMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'sac_ticket_id',
        'user_id',
        'mensagem',
        'tipo',
        'arquivo_url',
        'lida',
    ];

    protected function casts(): array
    {
        return [
            'lida' => 'boolean',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SacTicket::class, 'sac_ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
