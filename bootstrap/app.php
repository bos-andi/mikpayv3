<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => \App\Http\Middleware\IdentifyTenant::class,
            'superadmin' => \App\Http\Middleware\EnsureSuperAdmin::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'tenant.subscription' => \App\Http\Middleware\EnsureTenantSubscription::class,
            'tenant.host' => \App\Http\Middleware\RedirectTenantToOwnHost::class,
            'tenant.membership' => \App\Http\Middleware\EnsureTenantMembership::class,
            'tenant.can' => \App\Http\Middleware\EnsureTenantPermission::class,
            'central.domain' => \App\Http\Middleware\EnsureCentralDomain::class,
            'tenant.subdomain' => \App\Http\Middleware\EnsureKnownTenantSubdomain::class,
            'partner' => \App\Http\Middleware\EnsurePartner::class,
            'verified.email' => \App\Http\Middleware\EnsureEmailVerified::class,
        ]);

        $middleware->web(prepend: [
            \App\Http\Middleware\EnsureKnownTenantSubdomain::class,
        ], append: [
            \App\Http\Middleware\CaptureReferral::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/xendit',
            'webhooks/andidev-pay',
        ]);

        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo(function () {
            $user = auth()->user();

            if ($user?->isPartner()) {
                return central_url('/affiliate/dashboard');
            }

            return $user?->isSuperAdmin()
                ? central_url('/superadmin')
                : '/dashboard';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (\Illuminate\Routing\Exceptions\InvalidSignatureException $e, Request $request) {
            if (! $request->routeIs('verification.verify')) {
                return null;
            }

            $message = 'Tautan verifikasi sudah kedaluwarsa atau tidak valid. Kirim ulang tautan baru di bawah.';

            return $request->user()
                ? redirect()->away(central_url('/email/verifikasi'))->with('error', $message)
                : redirect()->away(central_url('/login'))->with('error', 'Tautan verifikasi sudah kedaluwarsa atau tidak valid. Silakan masuk untuk meminta tautan baru.');
        });
    })->create();
