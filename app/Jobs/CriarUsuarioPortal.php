<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class CriarUsuarioPortal implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order)
    {
    }

    /**
     * Cria ou atualiza o usuário cliente com token de acesso ao portal.
     */
    public function handle(): void
    {
        $lead = $this->order->lead;

        $user = User::find($this->order->user_id);

        if (! $user) {
            $user = User::create([
                'name' => $lead?->nome ?: 'Cliente Regulariza',
                'email' => $lead?->email ?: 'cliente+'.Str::lower(Str::random(12)).'@regulariza.local',
                'role' => 'cliente',
                'cpf_cnpj' => $lead?->cpf_cnpj,
                'whatsapp' => $lead?->whatsapp,
                'password' => Str::password(12),
            ]);

            $this->order->update(['user_id' => $user->id]);
        }

        $user->forceFill([
            'role' => 'cliente',
            'portal_token' => Str::random(64),
            'portal_token_expires_at' => now()->addDays(7),
        ])->save();
    }
}
