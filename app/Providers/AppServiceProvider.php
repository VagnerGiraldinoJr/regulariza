<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\SacTicket;
use App\Models\SystemSetting;
use App\Observers\OrderObserver;
use App\Policies\OrderPolicy;
use App\Policies\SacTicketPolicy;
use App\Services\ResearchProviderManager;
use App\Services\ResearchProviders\ApiBrasilResearchProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ResearchProviderManager::class, function ($app): ResearchProviderManager {
            return new ResearchProviderManager([
                $app->make(ApiBrasilResearchProvider::class),
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        SystemSetting::applyRuntimeConfig();

        Order::observe(OrderObserver::class);

        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(SacTicket::class, SacTicketPolicy::class);
    }
}
