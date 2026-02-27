<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SacSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'sac@regulariza.local'],
            [
                'name' => 'Atendente SAC',
                'role' => 'atendente',
                'password' => 'Sac@123456',
            ]
        );
    }
}
