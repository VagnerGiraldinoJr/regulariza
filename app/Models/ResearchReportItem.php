<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResearchReportItem extends Model
{
    protected $fillable = [
        'research_report_id',
        'apibrasil_consultation_id',
        'provider',
        'source_key',
        'source_title',
        'source_category',
        'status',
        'http_status',
        'request_payload',
        'response_payload',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(ResearchReport::class, 'research_report_id');
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(ApiBrasilConsultation::class, 'apibrasil_consultation_id');
    }
}
