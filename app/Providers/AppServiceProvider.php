<?php

namespace App\Providers;

use App\Models\SupportTicket;
use App\Services\OnboardingService;
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
            $tenant = current_tenant();
            $openTicketCount = 0;
            $onboarding = null;
            if ($tenant?->id) {
                $openTicketCount = SupportTicket::query()
                    ->where('tenant_id', $tenant->id)
                    ->whereIn('status', SupportTicket::OPEN_STATUSES)
                    ->count();

                $onboardingService = app(OnboardingService::class);
                if ($onboardingService->isEligible($tenant)) {
                    $onboarding = $onboardingService->checklist($tenant);
                }
            }

            $view->with([
                'activeRouter' => ActiveRouter::current(),
                'tenantRouters' => ActiveRouter::forTenant(),
                'activeRouterLabel' => ActiveRouter::label(),
                'openTicketCount' => $openTicketCount,
                'onboarding' => $onboarding,
            ]);
        });
    }
}
