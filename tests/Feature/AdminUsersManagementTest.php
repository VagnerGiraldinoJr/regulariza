<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUsersManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_user_email_and_password(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'role' => 'analista',
            'email' => 'analista.antigo@example.com',
            'password' => bcrypt('SenhaAntiga123'),
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.management.users.update', $user), [
            'name' => 'Analista Atualizado',
            'email' => 'analista.novo@example.com',
            'password' => 'NovaSenha123',
            'password_confirmation' => 'NovaSenha123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $user->refresh();

        $this->assertSame('Analista Atualizado', $user->name);
        $this->assertSame('analista.novo@example.com', $user->email);
        $this->assertTrue(Hash::check('NovaSenha123', (string) $user->password));
    }
}
