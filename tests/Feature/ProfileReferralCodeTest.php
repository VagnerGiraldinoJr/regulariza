<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileReferralCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_referral_code_and_it_is_normalized_to_lowercase(): void
    {
        $user = User::factory()->create([
            'role' => 'cliente',
            'referral_code' => 'codigoantigo',
        ]);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'whatsapp' => '11999999999',
            'referral_code' => 'MEUCODIGO123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'referral_code' => 'meucodigo123',
        ]);
    }

    public function test_user_cannot_use_duplicate_referral_code(): void
    {
        $existing = User::factory()->create([
            'role' => 'cliente',
            'referral_code' => 'codigoexistente',
        ]);

        $user = User::factory()->create([
            'role' => 'cliente',
            'referral_code' => 'codigoproprio',
        ]);

        $response = $this->actingAs($user)->from(route('profile.edit'))->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'whatsapp' => '11999999999',
            'referral_code' => strtoupper((string) $existing->referral_code),
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHasErrors('referral_code');
    }

    public function test_user_cannot_use_spaces_special_characters_or_accents_in_referral_code(): void
    {
        $user = User::factory()->create([
            'role' => 'cliente',
        ]);

        $response = $this->actingAs($user)->from(route('profile.edit'))->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'whatsapp' => '11999999999',
            'referral_code' => 'código teste!',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHasErrors('referral_code');
    }
}
