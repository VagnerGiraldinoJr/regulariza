<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Register the Horizon gate.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null): bool {
            if (! $user) {
                return false;
            }

            $role = mb_strtolower(trim((string) ($user->role ?? '')));

            return in_array($role, ['admin', 'administrador'], true);
        });
    }
}
