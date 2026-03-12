<?php

namespace App\Jobs;

use App\Mail\PortalAccessMail;
use App\Models\Order;
use App\Models\User;
use App\Services\LeadUserResolverService;
use App\Services\ZApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EnviarAcessoPortalWhatsApp implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order)
    {
    }

    public function handle(ZApiService $zApiService, LeadUserResolverService $leadUserResolverService): void
    {
        $this->order->loadMissing('lead');
        $lead = $this->order->lead;
        $user = User::find($this->order->user_id);

        if (! $user && $lead) {
            $user = $leadUserResolverService->resolve($lead);
        }

        if ($user && (int) $this->order->user_id !== (int) $user->id) {
            $this->order->update(['user_id' => $user->id]);
        }

        if (! $user) {
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

        $portalLink = route('portal.invite', $portalToken);

        if (! empty($user->whatsapp)) {
            try {
                $mensagem = $zApiService->renderTemplate('portal_acesso', [
                    'nome' => $user->name ?: 'cliente',
                    'link' => $portalLink,
                    'email' => $user->email,
                    'senha' => $temporaryPassword,
                ]);

                $zApiService->enviarMensagem(
                    telefone: (string) $user->whatsapp,
                    mensagem: $mensagem,
                    evento: 'portal_acesso',
                    userId: $user->id,
                    orderId: $this->order->id
                );
            } catch (\Throwable $exception) {
                Log::warning('Falha ao enviar acesso ao portal por WhatsApp.', [
                    'order_id' => $this->order->id,
                    'user_id' => $user->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if (filled($user->email) && ! $user->hasProvisionalEmail()) {
            try {
                Mail::to($user->email)->send(new PortalAccessMail(
                    user: $user->fresh(),
                    order: $this->order->fresh(),
                    accessLink: $portalLink,
                    temporaryPassword: $temporaryPassword
                ));
            } catch (\Throwable $exception) {
                Log::warning('Falha ao enviar acesso ao portal por e-mail.', [
                    'order_id' => $this->order->id,
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }
}
