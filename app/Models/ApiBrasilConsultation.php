<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiBrasilConsultation extends Model
{
    protected $fillable = [
        'order_id',
        'lead_id',
        'user_id',
        'admin_user_id',
        'analyst_user_id',
        'document_type',
        'document_number',
        'status',
        'provider',
        'endpoint',
        'http_status',
        'request_payload',
        'response_payload',
        'error_message',
        'forwarded_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
            'forwarded_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function analyst(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analyst_user_id');
    }
}
