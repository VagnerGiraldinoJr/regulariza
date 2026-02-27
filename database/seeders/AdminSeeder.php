<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@regulariza.local'],
            [
                'name' => 'Administrador',
                'role' => 'admin',
                'password' => 'Admin@123456',
            ]
        );
    }
}
