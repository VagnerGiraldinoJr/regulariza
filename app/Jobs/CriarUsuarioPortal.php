<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use App\Services\LeadUserResolverService;
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
    public function handle(LeadUserResolverService $leadUserResolverService): void
    {
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

        $user->forceFill([
            'role' => 'cliente',
            'portal_token' => Str::random(64),
            'portal_token_expires_at' => now()->addDays(7),
        ])->save();
    }
}
