<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_throttled_after_repeated_invalid_attempts(): void
    {
        User::factory()->create([
            'email' => 'cliente@example.com',
            'password' => Hash::make('senha-correta-123'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $response = $this->post(route('login.attempt'), [
                'email' => 'cliente@example.com',
                'password' => 'senha-incorreta',
            ]);

            $response->assertSessionHasErrors('email');
        }

        $blocked = $this->post(route('login.attempt'), [
            'email' => 'cliente@example.com',
            'password' => 'senha-incorreta',
        ]);

        $blocked->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Muitas tentativas',
            session('errors')->first('email')
        );
    }
}
