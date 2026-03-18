<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProtocolsSeeder extends Seeder
{
    public function run(): void
    {
        $seller = User::query()->where('email', 'lucas.bahia@regulariza.br')->first();
        $cliente = User::query()->where('email', 'cliente@regulariza.br')->first();

        if (! $seller || ! $cliente) {
            return;
        }

        $services = Service::query()
            ->whereIn('slug', ['bacen', 'serasa', 'cnh'])
            ->orderBy('id')
            ->get()
            ->values();

        if ($services->isEmpty()) {
            return;
        }

        $afiliados = User::query()
            ->where('referred_by_user_id', $seller->id)
            ->orderBy('email')
            ->get()
            ->values();

        if ($afiliados->count() < 10) {
            return;
        }

        for ($i = 1; $i <= 10; $i++) {
            $protocol = 'DEMO-LUCAS-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $service = $services[($i - 1) % $services->count()];
            $afiliado = $afiliados[$i - 1];
            $isPending = $i <= 3;

            Order::query()->updateOrCreate(
                ['protocolo' => $protocol],
                [
                    'user_id' => $afiliado->id,
                    'service_id' => $service->id,
                    'status' => $isPending ? 'pendente' : ($i % 2 === 0 ? 'concluido' : 'em_andamento'),
                    'valor' => (float) $service->preco,
                    'pagamento_status' => $isPending ? 'aguardando' : 'pago',
                    'pago_em' => $isPending ? null : now()->subDays(11 - $i),
                    'referral_credit_amount' => $isPending ? 0 : 20,
                    'referral_credited_at' => $isPending ? null : now()->subDays(11 - $i),
                ]
            );
        }

        for ($i = 1; $i <= 3; $i++) {
            $protocol = 'DEMO-CLIENTE-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $service = $services[($i - 1) % $services->count()];

            Order::query()->updateOrCreate(
                ['protocolo' => $protocol],
                [
                    'user_id' => $cliente->id,
                    'service_id' => $service->id,
                    'status' => $i % 2 === 0 ? 'concluido' : 'em_andamento',
                    'valor' => (float) $service->preco,
                    'pagamento_status' => 'pago',
                    'pago_em' => now()->subDays(4 - $i),
                ]
            );
        }

        $seller->update([
            'referral_credits' => 140.00,
        ]);
    }
}
