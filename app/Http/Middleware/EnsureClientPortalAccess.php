<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientPortalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'cliente') {
            return $next($request);
        }

        $hasPortalAccess = $user->contracts()
            ->whereIn('status', ['ativo', 'concluido'])
            ->exists();

        if (! $hasPortalAccess) {
            return redirect()->route('regularizacao.index')
                ->with('access_error', 'Seu acesso ao portal será liberado após a confirmação do pagamento da entrada do contrato.');
        }

        return $next($request);
    }
}
