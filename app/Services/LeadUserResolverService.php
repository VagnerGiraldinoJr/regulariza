<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Str;

class LeadUserResolverService
{
    public function resolve(Lead $lead, ?User $preferredUser = null): User
    {
        $document = $this->normalizeDocument((string) $lead->cpf_cnpj);
        $email = $this->normalizeEmail($lead->email);

        $user = $preferredUser;

        if (! $user && $document !== '') {
            $user = User::withTrashed()->where('cpf_cnpj', $document)->first();
        }

        if (! $user && $email !== null) {
            $user = User::withTrashed()->where('email', $email)->first();
        }

        if ($user) {
            if (method_exists($user, 'trashed') && $user->trashed()) {
                $user->restore();
            }

            $this->syncExistingUser($user, $lead, $document, $email);

            return $user->fresh();
        }

        return User::query()->create([
            'name' => $this->resolvedName($lead),
            'email' => $email ?: $this->fallbackEmail(),
            'role' => 'cliente',
            'cpf_cnpj' => $document !== '' ? $document : null,
            'whatsapp' => $lead->whatsapp ?: null,
            'referred_by_user_id' => $lead->referred_by_user_id ?: null,
            'password' => Str::password(12),
        ]);
    }

    private function syncExistingUser(User $user, Lead $lead, string $document, ?string $email): void
    {
        $updates = [];
        $resolvedName = $this->resolvedName($lead);

        if (($user->name === null || trim((string) $user->name) === '' || $user->name === 'Cliente Regulariza') && $resolvedName !== 'Cliente Regulariza') {
            $updates['name'] = $resolvedName;
        }

        if (($user->cpf_cnpj === null || trim((string) $user->cpf_cnpj) === '') && $document !== '') {
            $updates['cpf_cnpj'] = $document;
        }

        if (($user->whatsapp === null || trim((string) $user->whatsapp) === '') && filled($lead->whatsapp)) {
            $updates['whatsapp'] = (string) $lead->whatsapp;
        }

        if (
            $email !== null
            && $email !== ''
            && $email !== $user->email
            && (
                $user->email === null
                || trim((string) $user->email) === ''
                || str_ends_with((string) $user->email, '@regulariza.local')
            )
            && ! User::withTrashed()->where('email', $email)->whereKeyNot($user->id)->exists()
        ) {
            $updates['email'] = $email;
        }

        if (
            ! $user->referred_by_user_id
            && $lead->referred_by_user_id
            && (int) $lead->referred_by_user_id !== (int) $user->id
        ) {
            $updates['referred_by_user_id'] = $lead->referred_by_user_id;
        }

        if ($updates !== []) {
            $user->update($updates);
        }
    }

    private function normalizeDocument(string $document): string
    {
        return preg_replace('/\D+/', '', $document) ?? '';
    }

    private function normalizeEmail(?string $email): ?string
    {
        $normalized = mb_strtolower(trim((string) $email));

        return $normalized !== '' ? $normalized : null;
    }

    private function resolvedName(Lead $lead): string
    {
        $name = trim((string) $lead->nome);

        return $name !== '' ? $name : 'Cliente Regulariza';
    }

    private function fallbackEmail(): string
    {
        return 'cliente+'.Str::lower(Str::random(12)).'@regulariza.local';
    }
}
