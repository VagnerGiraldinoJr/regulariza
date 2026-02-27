<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSacTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'order_id' => [
                'nullable',
                'integer',
                Rule::exists('orders', 'id')->where(
                    fn ($query) => $query->where('user_id', $this->user()?->id)
                ),
            ],
            'assunto' => ['required', 'string', 'max:255'],
            'prioridade' => ['nullable', 'in:nova,baixa,media,alta,critica'],
            'mensagem' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
