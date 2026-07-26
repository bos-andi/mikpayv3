<?php

namespace App\Providers;

use App\Support\ActiveRouter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['layouts.panel', 'partials.panel.*', 'panel.*'], function ($view) {
            $view->with([
                'activeRouter' => ActiveRouter::current(),
                'tenantRouters' => ActiveRouter::forTenant(),
                'activeRouterLabel' => ActiveRouter::label(),
            ]);
        });
    }
}
