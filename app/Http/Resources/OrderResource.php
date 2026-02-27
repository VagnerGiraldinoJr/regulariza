<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'protocolo' => $this->protocolo,
            'status' => $this->status,
            'pagamento_status' => $this->pagamento_status,
            'valor' => $this->valor,
            'pago_em' => $this->pago_em,
            'service' => [
                'id' => $this->service?->id,
                'nome' => $this->service?->nome,
            ],
            'created_at' => $this->created_at,
        ];
    }
}
