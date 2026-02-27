<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSacTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'assunto' => ['required', 'string', 'max:255'],
            'prioridade' => ['nullable', 'in:nova,baixa,media,alta,critica'],
            'mensagem' => ['nullable', 'string'],
        ];
    }
}
