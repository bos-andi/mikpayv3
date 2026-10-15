<?php

namespace App\Providers;

use App\Models\SupportTicket;
use App\Services\OnboardingService;
use App\Support\ActiveRouter;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;

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
        // Queue worker memakai ulang koneksi SMTP; Hostinger memutus koneksi idle sebelum
        // ambang ping default Symfony (100 dtk) sehingga muncul "421 timeout exceeded".
        Event::listen(MessageSending::class, function () {
            $transport = Mail::mailer()->getSymfonyTransport();
            if ($transport instanceof SmtpTransport) {
                $transport->setPingThreshold(10);
            }
        });

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
                if (auth()->user()?->canAccessModule(\App\Support\TenantAccess::ADMIN) && $onboardingService->isEligible($tenant)) {
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
