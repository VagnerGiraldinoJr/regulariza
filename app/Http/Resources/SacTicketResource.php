<?php

namespace App\Http\Resources;

use App\Models\SacTicket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SacTicket */
class SacTicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'protocolo' => $this->protocolo,
            'assunto' => $this->assunto,
            'status' => $this->status,
            'prioridade' => $this->prioridade,
            'order_id' => $this->order_id,
            'atendente_id' => $this->atendente_id,
            'created_at' => $this->created_at,
        ];
    }
}
