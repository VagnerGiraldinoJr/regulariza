<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortalInviteController extends Controller
{
    public function __invoke(Request $request, string $token): RedirectResponse
    {
        $user = User::query()
            ->where('portal_token', $token)
            ->firstOrFail();

        abort_if(
            $user->portal_token_expires_at?->isPast(),
            410,
            'Este link de acesso ao portal expirou.'
        );

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        $user->forceFill([
            'portal_token' => null,
            'portal_token_expires_at' => null,
        ])->save();

        return redirect()->route('portal.dashboard');
    }
}
