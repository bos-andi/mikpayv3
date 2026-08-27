<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\GisController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HotspotController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\PublicPayController;
use App\Http\Controllers\RouterController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\LandingController as SuperAdminLandingController;
use App\Http\Controllers\SuperAdmin\PaymentsController as SuperAdminPaymentsController;
use App\Http\Controllers\SuperAdmin\SubscriptionPricingController as SuperAdminSubscriptionPricingController;
use App\Http\Controllers\SuperAdmin\UsersController as SuperAdminUsersController;
use App\Http\Controllers\Tunnel\TunnelHomeController;
use App\Http\Controllers\Tunnel\TunnelRegisterController;
use App\Http\Controllers\XenditWebhookController;
use Illuminate\Support\Facades\Route;

$registerPanelPages = function (): void {
    Route::get('/dashboard', [PanelController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard/bandwidth', [PanelController::class, 'bandwidth'])->name('dashboard.bandwidth');
    Route::get('/gis', [GisController::class, 'index'])->name('gis');
    Route::get('/gis/data', [GisController::class, 'data'])->name('gis.data');
    Route::post('/gis', [GisController::class, 'store'])->name('gis.store');
    Route::put('/gis/{odp}', [GisController::class, 'update'])->name('gis.update');
    Route::delete('/gis/{odp}', [GisController::class, 'destroy'])->name('gis.destroy');
    Route::put('/gis/{odp}/ports', [GisController::class, 'updatePort'])->name('gis.ports.update');
    Route::get('/remote', [PanelController::class, 'remote'])->name('remote');
    Route::get('/customers', [CustomersController::class, 'index'])->name('customers');
    Route::get('/customers/edit', [CustomersController::class, 'edit'])->name('customers.edit');
    Route::post('/customers/update', [CustomersController::class, 'update'])->name('customers.update');
    Route::get('/customers/template', [CustomersController::class, 'downloadTemplate'])->name('customers.template');
    Route::post('/customers/import', [CustomersController::class, 'import'])->name('customers.import');
    Route::post('/customers/isolir', [CustomersController::class, 'isolir'])->name('customers.isolir');
    Route::post('/customers/open', [CustomersController::class, 'open'])->name('customers.open');
    Route::get('/billing', [BillingController::class, 'index'])->name('billing');
    Route::post('/billing/payments', [BillingController::class, 'storePayment'])->name('billing.payments.store');
    Route::delete('/billing/payments/{payment}', [BillingController::class, 'destroyPayment'])->name('billing.payments.destroy');
    Route::post('/billing/payment-links', [BillingController::class, 'createPaymentLink'])->name('billing.payment-links.store');
    Route::post('/billing/auto-isolir', [BillingController::class, 'runAutoIsolir'])->name('billing.auto-isolir');
    Route::post('/billing/isolir', [BillingController::class, 'isolir'])->name('billing.isolir');
    Route::post('/billing/open', [BillingController::class, 'open'])->name('billing.open');
    Route::post('/billing/promises', [BillingController::class, 'storePromise'])->name('billing.promises.store');
    Route::delete('/billing/promises', [BillingController::class, 'destroyPromise'])->name('billing.promises.destroy');
    Route::post('/billing/period-fees', [BillingController::class, 'storePeriodFee'])->name('billing.period-fees.store');
    Route::delete('/billing/period-fees', [BillingController::class, 'destroyPeriodFee'])->name('billing.period-fees.destroy');
    Route::get('/vouchers', [HotspotController::class, 'index'])->name('vouchers');
    Route::get('/vouchers/print', [HotspotController::class, 'print'])->name('vouchers.print');
    Route::post('/vouchers/generate', [HotspotController::class, 'storeGenerate'])->name('vouchers.generate');
    Route::post('/vouchers/profiles', [HotspotController::class, 'storeProfile'])->name('vouchers.profiles.store');
    Route::post('/vouchers/users', [HotspotController::class, 'storeUser'])->name('vouchers.users.store');
    Route::post('/vouchers/users/remove-by-comment', [HotspotController::class, 'removeByComment'])->name('vouchers.users.remove-by-comment');
    Route::post('/vouchers/users/remove-expired', [HotspotController::class, 'removeExpired'])->name('vouchers.users.remove-expired');
    Route::post('/vouchers/users/{id}/{action}', [HotspotController::class, 'userAction'])
        ->whereIn('action', ['enable', 'disable', 'remove'])
        ->name('vouchers.users.action');
    Route::post('/vouchers/template', [HotspotController::class, 'saveTemplate'])->name('vouchers.template.save');
    Route::post('/vouchers/template/preview', [HotspotController::class, 'previewTemplate'])->name('vouchers.template.preview');
    Route::get('/finance', [FinanceController::class, 'index'])->name('finance');
    Route::post('/finance/cash', [FinanceController::class, 'storeCash'])->name('finance.cash.store');
    Route::post('/finance/assets', [FinanceController::class, 'storeAsset'])->name('finance.assets.store');
    Route::delete('/finance/cash/{cash}', [FinanceController::class, 'destroyCash'])->name('finance.cash.destroy');
    Route::post('/finance/settings', [FinanceController::class, 'updateSettings'])->name('finance.settings.update');
    Route::post('/finance/debts', [FinanceController::class, 'storeDebt'])->name('finance.debts.store');
    Route::post('/finance/debts/{debt}/paid', [FinanceController::class, 'markDebtPaid'])->name('finance.debts.paid');
    Route::delete('/finance/debts/{debt}', [FinanceController::class, 'destroyDebt'])->name('finance.debts.destroy');
    Route::get('/finance/export/csv', [FinanceController::class, 'exportCsv'])->name('finance.export.csv');
    Route::get('/finance/export/pdf', [FinanceController::class, 'exportPdf'])->name('finance.export.pdf');
    Route::get('/tickets', [PanelController::class, 'tickets'])->name('tickets');
    Route::get('/settings', [PanelController::class, 'settings'])->name('settings');
    Route::post('/settings/xendit', [SettingsController::class, 'updateXendit'])->name('settings.xendit.update');
    Route::post('/settings/whatsapp', [SettingsController::class, 'updateWhatsapp'])->name('settings.whatsapp.update');
    Route::post('/settings/whatsapp/test', [SettingsController::class, 'testWhatsapp'])->name('settings.whatsapp.test');
    Route::post('/settings/billing', [SettingsController::class, 'updateBilling'])->name('settings.billing.update');

    Route::get('/langganan', [SubscriptionController::class, 'index'])->name('subscription');
    Route::post('/langganan/pilih', [SubscriptionController::class, 'selectPackage'])->name('subscription.select');
    Route::get('/langganan/pembayaran', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::post('/langganan/batal', [SubscriptionController::class, 'cancelCheckout'])->name('subscription.cancel');
    Route::post('/langganan/batalkan-pending', [SubscriptionController::class, 'cancelPending'])->name('subscription.cancel-pending');
    Route::post('/langganan/unggah', [SubscriptionController::class, 'requestPayment'])->name('subscription.pay');

    Route::get('/settings/routers/create', [RouterController::class, 'create'])->name('settings.routers.create');
    Route::post('/settings/routers', [RouterController::class, 'store'])->name('settings.routers.store');
    Route::get('/settings/routers/{router}/edit', [RouterController::class, 'edit'])->name('settings.routers.edit');
    Route::put('/settings/routers/{router}', [RouterController::class, 'update'])->name('settings.routers.update');
    Route::delete('/settings/routers/{router}', [RouterController::class, 'destroy'])->name('settings.routers.destroy');
    Route::post('/settings/routers/{router}/test', [RouterController::class, 'test'])->name('settings.routers.test');
    Route::post('/settings/routers/{router}/select', [RouterController::class, 'select'])->name('settings.routers.select');
};

$registerPublicPayPages = function (): void {
    Route::get('/bayar', [PublicPayController::class, 'lookupForm'])->name('pay.lookup');
    Route::post('/bayar', [PublicPayController::class, 'lookup'])->name('pay.lookup.submit');
    Route::get('/bayar/{token}', [PublicPayController::class, 'show'])->name('pay.show');
    Route::post('/bayar/{token}/checkout', [PublicPayController::class, 'checkout'])->name('pay.checkout');
    Route::get('/bayar/{token}/metode', [PublicPayController::class, 'methods'])->name('pay.methods');
    Route::post('/bayar/{token}/metode', [PublicPayController::class, 'createMethod'])->name('pay.methods.create');
    Route::get('/bayar/{token}/instruksi', [PublicPayController::class, 'instruction'])->name('pay.instruction');
    Route::get('/bayar/{token}/status', [PublicPayController::class, 'status'])->name('pay.status');
};

/*
| Marketing
*/
Route::get('/', [HomeController::class, 'index'])->name('home');

/*
| Xendit webhook (central, no auth / no CSRF)
*/
Route::post('/webhooks/xendit', XenditWebhookController::class)->name('webhooks.xendit');

/*
| Auth
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/daftar', [RegisterController::class, 'create'])->name('register');
    Route::post('/daftar', [RegisterController::class, 'store'])->name('register.store');
    Route::get('/daftar/cek-subdomain', [RegisterController::class, 'checkSubdomain'])->name('register.check-subdomain');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
| Superadmin
*/
Route::prefix('superadmin')
    ->name('superadmin.')
    ->middleware(['auth', 'active', 'superadmin'])
    ->group(function () {
        Route::get('/', SuperAdminDashboardController::class)->name('dashboard');
        Route::get('/users', [SuperAdminUsersController::class, 'index'])->name('users');
        Route::put('/tenants/{tenant}', [SuperAdminUsersController::class, 'updateTenant'])->name('tenants.update');
        Route::post('/tenants/{tenant}/toggle', [SuperAdminUsersController::class, 'toggleTenantStatus'])->name('tenants.toggle');
        Route::post('/tenants/{tenant}/users', [SuperAdminUsersController::class, 'storeTenantUser'])->name('tenants.users.store');
        Route::put('/users/{user}', [SuperAdminUsersController::class, 'updateUser'])->name('users.update');
        Route::post('/users/{user}/toggle', [SuperAdminUsersController::class, 'toggleUserActive'])->name('users.toggle');

        Route::get('/landing', [SuperAdminLandingController::class, 'edit'])->name('landing');
        Route::post('/landing', [SuperAdminLandingController::class, 'update'])->name('landing.update');

        Route::get('/paket', [SuperAdminSubscriptionPricingController::class, 'edit'])->name('subscription-pricing');
        Route::post('/paket', [SuperAdminSubscriptionPricingController::class, 'update'])->name('subscription-pricing.update');

        Route::get('/pembayaran', [SuperAdminPaymentsController::class, 'index'])->name('payments');
        Route::post('/pembayaran/{payment}/approve', [SuperAdminPaymentsController::class, 'approve'])->name('payments.approve');
        Route::post('/pembayaran/{payment}/reject', [SuperAdminPaymentsController::class, 'reject'])->name('payments.reject');
    });

/*
| ISP Panel
*/
Route::middleware(['auth', 'active', 'tenant', 'tenant.host', 'tenant.subscription'])
    ->name('panel.')
    ->group($registerPanelPages);

/*
| Legacy redirects
*/
Route::redirect('/app', '/dashboard');
Route::redirect('/user', '/dashboard');
Route::get('/app/{path}', fn (string $path) => redirect('/'.$path))->where('path', '.*');
Route::get('/user/{path}', fn (string $path) => redirect('/'.$path))->where('path', '.*');

/*
| MikPay Tunnel product site — must be registered BEFORE {tenant}.mikpay.link
| so "tunnel" is not captured as a tenant subdomain.
*/
Route::domain(config('mikpay.tunnel_domain', 'tunnel.mikpay.link'))
    ->name('tunnel.')
    ->group(function () {
        Route::get('/', [TunnelHomeController::class, 'landing'])->name('home');

        Route::middleware('guest')->group(function () {
            Route::get('/daftar', [TunnelRegisterController::class, 'create'])->name('register');
            Route::post('/daftar', [TunnelRegisterController::class, 'store'])->name('register.store');
        });

        Route::middleware(['auth', 'active', 'tenant'])->group(function () {
            Route::get('/dashboard', [TunnelHomeController::class, 'dashboard'])->name('dashboard');
        });
    });

/*
| Public pay pages on tenant host (no auth)
| Exclude reserved platform subs (tunnel, www, remote, ...).
*/
// Exclude platform subs + VPN node hosts (an-1, cgk-2, ...).
$tenantSubPattern = '^(?!www$|app$|user$|mail$|api$|admin$|remote$|tunnel$|cdn$|static$|(?:an|cgk)-[0-9]+$)[a-z0-9-]+$';

Route::domain('{tenant}.'.config('mikpay.tenant_base_domain'))
    ->where(['tenant' => $tenantSubPattern])
    ->middleware(['tenant'])
    ->name('tenant.')
    ->group(function () use ($registerPublicPayPages) {
        $registerPublicPayPages();
    });

/*
| Client tenant panel
*/
Route::domain('{tenant}.'.config('mikpay.tenant_base_domain'))
    ->where(['tenant' => $tenantSubPattern])
    ->middleware(['auth', 'active', 'tenant', 'tenant.subscription'])
    ->name('tenant.')
    ->group(function () use ($registerPanelPages) {
        Route::redirect('/', '/dashboard');
        $registerPanelPages();
    });

/*
| Public pay on central (token still works; tenant resolved from link)
*/
Route::middleware(['tenant'])
    ->name('panel.')
    ->group($registerPublicPayPages);