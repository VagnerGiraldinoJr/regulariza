<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResearchReport extends Model
{
    protected $fillable = [
        'order_id',
        'lead_id',
        'user_id',
        'admin_user_id',
        'analyst_user_id',
        'report_type',
        'title',
        'document_type',
        'document_number',
        'status',
        'source_count',
        'success_count',
        'failure_count',
        'normalized_payload',
        'notes',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'normalized_payload' => 'array',
            'generated_at' => 'datetime',
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

    public function items(): HasMany
    {
        return $this->hasMany(ResearchReportItem::class);
    }
}
