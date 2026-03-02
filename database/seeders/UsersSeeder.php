<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@regulariza.br'],
            [
                'name' => 'Administrator',
                'role' => 'admin',
                'password' => 'Admin@123',
            ]
        );

        User::updateOrCreate(
            ['email' => 'sac@regulariza.br'],
            [
                'name' => 'Suporte',
                'role' => 'atendente',
                'password' => 'Sac@123',
            ]
        );

        $cliente = User::updateOrCreate(
            ['email' => 'cliente@regulariza.br'],
            [
                'name' => 'Cliente Teste',
                'role' => 'cliente',
                'password' => 'Cliente@123',
                'cpf_cnpj' => '12345678909',
                'whatsapp' => '11999999999',
            ]
        );

        $seller = User::updateOrCreate(
            ['email' => 'lucas.bahia@regulariza.br'],
            [
                'name' => 'Lucas-Bahia',
                'role' => 'cliente',
                'password' => 'Lucas@123',
                'cpf_cnpj' => '98765432100',
                'whatsapp' => '71999990001',
                'referral_code' => 'LUCASBAHIA',
            ]
        );

        if (empty($cliente->referral_code)) {
            $cliente->ensureReferralCode();
        }

        for ($i = 1; $i <= 10; $i++) {
            $index = str_pad((string) $i, 2, '0', STR_PAD_LEFT);

            User::updateOrCreate(
                ['email' => "afiliado{$index}@regulariza.br"],
                [
                    'name' => "Afiliado {$index} - Lucas",
                    'role' => 'cliente',
                    'password' => 'Cliente@123',
                    'cpf_cnpj' => '7000000'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                    'whatsapp' => '7199999'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                    'referred_by_user_id' => $seller->id,
                ]
            );
        }
    }
}

