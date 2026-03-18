<?php

namespace App\Http\Controllers;

use App\Models\SacMessage;
use App\Models\SacTicket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PublicContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'nome' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150'],
            'whatsapp' => ['required', 'string', 'max:20'],
            'cnpj' => ['nullable', 'string', 'max:18'],
            'mensagem' => ['nullable', 'string', 'max:1000'],
            'origem' => ['nullable', 'string', 'max:120'],
        ], [
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'whatsapp.required' => 'Informe o WhatsApp com DDD.',
            'whatsapp.max' => 'WhatsApp inválido.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'publicWhatsapp')
                ->withInput();
        }

        $validated = $validator->validated();

        $whatsappDigits = preg_replace('/\D+/', '', (string) $validated['whatsapp']);
        $cnpjDigits = preg_replace('/\D+/', '', (string) ($validated['cnpj'] ?? ''));
        $origem = (string) ($validated['origem'] ?? 'site');

        if (strlen($whatsappDigits) < 10 || strlen($whatsappDigits) > 13) {
            return back()
                ->withErrors(['whatsapp' => 'Informe um WhatsApp válido com DDD.'], 'publicWhatsapp')
                ->withInput();
        }

        if (($origem === '/' || $origem === 'welcome') && strlen($cnpjDigits) !== 14) {
            return back()
                ->withErrors(['cnpj' => 'Informe um CNPJ válido com 14 dígitos.'], 'publicWhatsapp')
                ->withInput();
        }

        $user = User::query()->firstOrCreate(
            ['email' => (string) $validated['email']],
            [
                'name' => (string) ($validated['nome'] ?? 'Lead Site'),
                'role' => 'cliente',
                'whatsapp' => $whatsappDigits,
                'password' => Str::password(16),
            ]
        );

        $user->update([
            'name' => $validated['nome'] ?: $user->name,
            'whatsapp' => $whatsappDigits,
        ]);

        $ticket = SacTicket::query()->create([
            'user_id' => $user->id,
            'assunto' => 'Contato público via WhatsApp - site',
            'prioridade' => 'nova',
            'status' => 'aberto',
        ]);

        $mensagem = trim((string) ($validated['mensagem'] ?? ''));

        SacMessage::query()->create([
            'sac_ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'mensagem' => trim(
                "Captação de contato público\n".
                "Origem: {$origem}\n".
                'Nome: '.($validated['nome'] ?: 'Não informado')."\n".
                "E-mail: {$validated['email']}\n".
                "WhatsApp: {$whatsappDigits}\n".
                'CNPJ: '.($cnpjDigits !== '' ? $cnpjDigits : 'Não informado')."\n".
                ($mensagem !== '' ? "Mensagem: {$mensagem}" : 'Mensagem: Não informada')
            ),
            'tipo' => 'texto',
        ]);

        return back()->with('public_whatsapp_success', 'Contato enviado com sucesso. Nosso SAC vai falar com você em breve.');
    }
}
