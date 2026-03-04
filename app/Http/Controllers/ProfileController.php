<?php

namespace App\Http\Controllers;

use App\Services\ZApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('profile.edit', [
            'user' => $user,
            'isSeller' => in_array($user->role, ['analista', 'vendedor'], true),
        ]);
    }

    public function update(Request $request, ZApiService $zApiService): RedirectResponse
    {
        $user = $request->user();
        $isSeller = in_array($user->role, ['analista', 'vendedor'], true);

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'whatsapp' => ['required', 'string', 'max:20'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ];

        if ($isSeller) {
            $rules = array_merge($rules, [
                'pix_key' => ['nullable', 'string', 'max:120'],
                'pix_key_type' => ['nullable', Rule::in(['cpf', 'cnpj', 'email', 'telefone', 'aleatoria'])],
                'pix_holder_name' => ['nullable', 'string', 'max:120'],
                'pix_holder_document' => ['nullable', 'string', 'max:20'],
            ]);
        }

        $data = $request->validate($rules);

        $oldWhatsapp = preg_replace('/\D+/', '', (string) $user->whatsapp);
        $newWhatsapp = preg_replace('/\D+/', '', (string) ($data['whatsapp'] ?? ''));

        $user->name = $data['name'];
        $user->email = mb_strtolower(trim((string) $data['email']));
        $user->whatsapp = $newWhatsapp;

        if ($isSeller) {
            $user->pix_key = $data['pix_key'] ?? null;
            $user->pix_key_type = $data['pix_key_type'] ?? null;
            $user->pix_holder_name = $data['pix_holder_name'] ?? null;
            $user->pix_holder_document = isset($data['pix_holder_document'])
                ? preg_replace('/\D+/', '', (string) $data['pix_holder_document'])
                : null;
        }

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');

            if (! empty($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $user->avatar_path = $path;
        }

        $user->save();

        if ($newWhatsapp !== '' && $newWhatsapp !== $oldWhatsapp) {
            $zApiService->enviarMensagem(
                $newWhatsapp,
                'Validação CPF Clean Brasil: recebemos a solicitação de troca do seu celular neste cadastro. Se não foi você, avise o suporte imediatamente.',
                'status_atualizado',
                $user->id,
                null
            );
        }

        return back()->with('success', 'Perfil atualizado com sucesso.');
    }
}
