<?php

namespace App\Providers;

use App\Models\Subscription;
use App\Models\User;
use App\Support\SubscriptionGate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Carbon\Carbon;

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
        View::composer(['layouts.dashboard', 'layouts.app'], function ($view) {
            $user = Auth::user();
            $endDate = null;

            if ($user instanceof User && ! empty($user->subscription_end)) {
                try {
                    $endDate = Carbon::parse($user->subscription_end);
                } catch (\Throwable $e) {
                    $endDate = null;
                }
            } else {
                // Fallback untuk halaman/role legacy yang masih memakai subscription global.
                $subscription = Subscription::getGlobal();
                $endDate = $subscription->end_date ? Carbon::parse($subscription->end_date) : null;
            }

            $subscription_until = $endDate ? $endDate->format('d M Y') : null;
            $subscription_remaining_days = $endDate ? now()->diffInDays($endDate, false) : null;

            $fullDashboardNav = ! ($user instanceof User) || SubscriptionGate::allowsAppAccess($user);

            $view->with([
                'subscription_until' => $subscription_until,
                'subscription_remaining_days' => $subscription_remaining_days,
                'full_dashboard_nav' => $fullDashboardNav,
            ]);
        });
    }
}
