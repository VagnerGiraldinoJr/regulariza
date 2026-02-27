<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Permite acesso apenas para usuários com os papéis informados na rota.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! in_array($user->role, $roles, true)) {
            if ($request->expectsJson()) {
                abort(403);
            }

            $target = match ($user->role) {
                'admin', 'atendente' => route('admin.orders.index'),
                default => route('portal.dashboard'),
            };

            return redirect($target)->with('access_error', 'Você não tem permissão para acessar esta área.');
        }

        return $next($request);
    }
}
