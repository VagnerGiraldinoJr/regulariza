<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'cliente@regulariza.local'],
            [
                'name' => 'Cliente Teste',
                'role' => 'cliente',
                'password' => 'Cliente@123456',
                'cpf_cnpj' => '12345678909',
                'whatsapp' => '11999999999',
            ]
        );
    }
}
