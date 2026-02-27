<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        $cliente = User::updateOrCreate(
            ['email' => 'cliente@regulariza.local'],
            [
                'name' => 'Cliente Teste',
                'role' => 'cliente',
                'password' => 'Cliente@123456',
                'cpf_cnpj' => '12345678909',
                'whatsapp' => '11999999999',
            ]
        );

        $cliente->ensureReferralCode();

        $service = Service::query()->where('ativo', true)->orderByDesc('id')->first();

        if (! $service) {
            return;
        }

        Order::query()->updateOrCreate(
            ['protocolo' => 'PED-DEMO-CLIENTE-001'],
            [
                'user_id' => $cliente->id,
                'service_id' => $service->id,
                'status' => 'em_andamento',
                'valor' => $service->preco,
                'pagamento_status' => 'pago',
                'pago_em' => now(),
            ]
        );
    }
}
