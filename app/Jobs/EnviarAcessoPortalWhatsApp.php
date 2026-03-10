<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use App\Services\ZApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EnviarAcessoPortalWhatsApp implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order)
    {
    }

    public function handle(ZApiService $zApiService): void
    {
        $this->order->loadMissing('lead');
        $lead = $this->order->lead;
        $user = User::find($this->order->user_id);

        if (! $user) {
            $user = User::create([
                'name' => $lead?->nome ?: 'Cliente CPF Clean',
                'email' => $lead?->email ?: 'cliente+'.Str::lower(Str::random(12)).'@regulariza.local',
                'role' => 'cliente',
                'cpf_cnpj' => $lead?->cpf_cnpj,
                'whatsapp' => $lead?->whatsapp,
                'password' => Str::password(10),
            ]);

            $this->order->update(['user_id' => $user->id]);
        }

        if (empty($user->whatsapp)) {
            return;
        }

        $temporaryPassword = strtoupper(Str::random(4)).strtolower(Str::random(4));
        $portalToken = Str::random(64);

        $user->forceFill([
            'role' => 'cliente',
            'password' => Hash::make($temporaryPassword),
            'portal_token' => $portalToken,
            'portal_token_expires_at' => now()->addDays(7),
        ])->save();

        $mensagem = $zApiService->renderTemplate('portal_acesso', [
            'nome' => $user->name,
            'link' => route('portal.invite', $portalToken),
            'email' => $user->email,
            'senha' => $temporaryPassword,
        ]);

        $zApiService->enviarMensagem(
            telefone: (string) $user->whatsapp,
            mensagem: $mensagem,
            evento: 'pagamento_confirmado',
            userId: $user->id,
            orderId: $this->order->id
        );
    }
}
