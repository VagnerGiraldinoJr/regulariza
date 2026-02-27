<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSacMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'mensagem' => ['required', 'string'],
            'tipo' => ['nullable', 'in:texto,arquivo,sistema'],
            'arquivo_url' => ['nullable', 'url'],
        ];
    }
}
