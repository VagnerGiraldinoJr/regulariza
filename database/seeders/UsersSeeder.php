<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $adminPassword = env('SEED_ADMIN_PASSWORD');
        $supportPassword = env('SEED_SUPPORT_PASSWORD');
        $clientPassword = env('SEED_CLIENT_PASSWORD');
        $analystPassword = env('SEED_ANALYST_PASSWORD');
        $sellerPassword = env('SEED_SELLER_PASSWORD');

        User::updateOrCreate(
            ['email' => 'admin@cpfclean.com.br'],
            [
                'name' => 'Administrator',
                'role' => 'admin',
                'password' => $adminPassword ?: Str::random(24),
            ]
        );

        User::updateOrCreate(
            ['email' => 'sac@cpfclean.com.br'],
            [
                'name' => 'Suporte',
                'role' => 'atendente',
                'password' => $supportPassword ?: Str::random(24),
            ]
        );

        $cliente = User::updateOrCreate(
            ['email' => 'cliente@cpfclean.com.br'],
            [
                'name' => 'Cliente Teste',
                'role' => 'cliente',
                'password' => $clientPassword ?: Str::random(24),
                'cpf_cnpj' => '12345678909',
                'whatsapp' => '11999999999',
            ]
        );

        $seller = User::updateOrCreate(
            ['email' => 'lucas.bahia@cpfclean.com.br'],
            [
                'name' => 'Lucas-Bahia',
                'role' => 'cliente',
                'password' => $sellerPassword ?: Str::random(24),
                'cpf_cnpj' => '98765432100',
                'whatsapp' => '71999990001',
                'referral_code' => 'LUCASBAHIA',
            ]
        );

        User::updateOrCreate(
            ['email' => 'analista@cpfclean.com.br'],
            [
                'name' => 'Analista CPFClean',
                'role' => 'analista',
                'password' => $analystPassword ?: Str::random(24),
                'cpf_cnpj' => '11222333000199',
                'whatsapp' => '31999990002',
                'referral_code' => 'ANALISTA01',
            ]
        );

        if (empty($cliente->referral_code)) {
            $cliente->ensureReferralCode();
        }

        for ($i = 1; $i <= 10; $i++) {
            $index = str_pad((string) $i, 2, '0', STR_PAD_LEFT);

            User::updateOrCreate(
                ['email' => "afiliado{$index}@cpfclean.com.br"],
                [
                    'name' => "Afiliado {$index} - Lucas",
                    'role' => 'cliente',
                    'password' => $clientPassword ?: Str::random(24),
                    'cpf_cnpj' => '7000000'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                    'whatsapp' => '7199999'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                    'referred_by_user_id' => $seller->id,
                ]
            );
        }

        if ($this->command && (! $adminPassword || ! $supportPassword || ! $clientPassword || ! $analystPassword || ! $sellerPassword)) {
            $this->command->warn('UsersSeeder executado com senhas aleatorias para contas padrao. Defina SEED_*_PASSWORD apenas em ambiente local se precisar credenciais conhecidas.');
        }
    }
}
