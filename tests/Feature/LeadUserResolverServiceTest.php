<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use App\Services\LeadUserResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadUserResolverServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_fallback_email_with_mail_from_domain(): void
    {
        config()->set('mail.from.address', 'contato@cpfclean.com.br');

        $lead = Lead::query()->create([
            'cpf_cnpj' => '36745465825',
            'tipo_documento' => 'cpf',
            'nome' => 'Cliente Sem Email',
            'email' => null,
        ]);

        $user = app(LeadUserResolverService::class)->resolve($lead);

        $this->assertStringStartsWith('cliente+', $user->email);
        $this->assertStringEndsWith('@cpfclean.com.br', $user->email);
    }

    public function test_it_replaces_legacy_fallback_email_when_lead_receives_real_email(): void
    {
        config()->set('mail.from.address', 'contato@cpfclean.com.br');

        $user = User::factory()->create([
            'email' => 'cliente+abc123@regulariza.local',
            'cpf_cnpj' => '36745465825',
        ]);

        $lead = Lead::query()->create([
            'cpf_cnpj' => '36745465825',
            'tipo_documento' => 'cpf',
            'nome' => 'Cliente Atualizado',
            'email' => 'cliente.real@example.com',
        ]);

        $resolved = app(LeadUserResolverService::class)->resolve($lead, $user);

        $this->assertSame('cliente.real@example.com', $resolved->email);
    }

    public function test_it_migrates_legacy_fallback_email_to_current_domain_when_no_real_email_is_available(): void
    {
        config()->set('mail.from.address', 'contato@cpfclean.com.br');

        $user = User::factory()->create([
            'email' => 'cliente+abc123@regulariza.local',
            'cpf_cnpj' => '36745465825',
        ]);

        $lead = Lead::query()->create([
            'cpf_cnpj' => '36745465825',
            'tipo_documento' => 'cpf',
            'nome' => 'Cliente Atualizado',
            'email' => null,
        ]);

        $resolved = app(LeadUserResolverService::class)->resolve($lead, $user);

        $this->assertSame('cliente+abc123@cpfclean.com.br', $resolved->email);
    }
}
