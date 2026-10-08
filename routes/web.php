<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\GisController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HotspotController;
use App\Http\Controllers\OltController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\Partner\PartnerPanelController;
use App\Http\Controllers\Partner\PartnerPublicController;
use App\Http\Controllers\TicketsController;
use App\Http\Controllers\PublicPayController;
use App\Http\Controllers\RouterController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\LandingController as SuperAdminLandingController;
use App\Http\Controllers\SuperAdmin\PartnersController as SuperAdminPartnersController;
use App\Http\Controllers\SuperAdmin\PaymentsController as SuperAdminPaymentsController;
use App\Http\Controllers\SuperAdmin\SubscriptionPricingController as SuperAdminSubscriptionPricingController;
use App\Http\Controllers\SuperAdmin\UsersController as SuperAdminUsersController;
use App\Http\Controllers\SuperAdmin\WithdrawalsController as SuperAdminWithdrawalsController;
use App\Http\Controllers\Tunnel\TunnelHomeController;
use App\Http\Controllers\Tunnel\TunnelRegisterController;
use App\Http\Controllers\VoucherShopAdminController;
use App\Http\Controllers\VoucherShopController;
use App\Http\Controllers\WithdrawalController;
use App\Http\Controllers\XenditWebhookController;
use Illuminate\Support\Facades\Route;

$registerPanelPages = function (): void {
    Route::get('/dashboard', [PanelController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard/bandwidth', [PanelController::class, 'bandwidth'])->name('dashboard.bandwidth');
    Route::get('/mulai', [OnboardingController::class, 'index'])->name('onboarding');
    Route::post('/mulai/script', [OnboardingController::class, 'confirmScript'])->name('onboarding.script');
    Route::post('/mulai/dismiss', [OnboardingController::class, 'dismiss'])->name('onboarding.dismiss');
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
    Route::post('/customers/store', [CustomersController::class, 'store'])->middleware('throttle:30,1')->name('customers.store');
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
    Route::get('/vouchers/toko', [VoucherShopAdminController::class, 'index'])->name('vouchers.shop');
    Route::post('/vouchers/toko/pengaturan', [VoucherShopAdminController::class, 'saveSettings'])->name('vouchers.shop.settings');
    Route::post('/vouchers/toko/paket', [VoucherShopAdminController::class, 'storeItem'])->name('vouchers.shop.items.store');
    Route::put('/vouchers/toko/paket/{item}', [VoucherShopAdminController::class, 'updateItem'])->name('vouchers.shop.items.update');
    Route::delete('/vouchers/toko/paket/{item}', [VoucherShopAdminController::class, 'destroyItem'])->name('vouchers.shop.items.destroy');
    Route::post('/vouchers/toko/pesanan/{order}/ulang', [VoucherShopAdminController::class, 'retryOrder'])->name('vouchers.shop.orders.retry');
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
    Route::get('/finance/penarikan', [WithdrawalController::class, 'index'])->name('finance.withdrawals');
    Route::post('/finance/penarikan', [WithdrawalController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('finance.withdrawals.store');
    Route::post('/finance/penarikan/rekening', [WithdrawalController::class, 'saveBank'])
        ->middleware('throttle:10,1')
        ->name('finance.withdrawals.bank');
    Route::get('/finance/penarikan/rekening/{account}/bukti', [WithdrawalController::class, 'proof'])->name('finance.withdrawals.bank.proof');
    Route::post('/finance/penarikan/{withdrawal}/batal', [WithdrawalController::class, 'cancel'])->name('finance.withdrawals.cancel');
    Route::get('/tickets', [TicketsController::class, 'index'])->name('tickets');
    Route::post('/tickets', [TicketsController::class, 'store'])->name('tickets.store');
    Route::put('/tickets/{ticket}', [TicketsController::class, 'update'])->name('tickets.update');
    Route::delete('/tickets/{ticket}', [TicketsController::class, 'destroy'])->name('tickets.destroy');
    Route::post('/olt', [OltController::class, 'store'])->name('olt.store');
    Route::put('/olt/{olt}', [OltController::class, 'update'])->name('olt.update');
    Route::delete('/olt/{olt}', [OltController::class, 'destroy'])->name('olt.destroy');
    Route::post('/olt/{olt}/poll', [OltController::class, 'poll'])->name('olt.poll');
    Route::post('/olt/poll-all', [OltController::class, 'pollAll'])->name('olt.poll-all');
    Route::post('/olt/sync', [OltController::class, 'sync'])->name('olt.sync');
    Route::get('/settings', [PanelController::class, 'settings'])->name('settings');
    Route::post('/settings/xendit', [SettingsController::class, 'updateXendit'])->name('settings.xendit.update');
    Route::post('/settings/whatsapp', [SettingsController::class, 'updateWhatsapp'])->name('settings.whatsapp.update');
    Route::post('/settings/whatsapp/test', [SettingsController::class, 'testWhatsapp'])->name('settings.whatsapp.test');
    Route::post('/settings/billing', [SettingsController::class, 'updateBilling'])->name('settings.billing.update');

    Route::get('/langganan', [SubscriptionController::class, 'index'])->name('subscription');
    Route::post('/langganan/deposit', [SubscriptionController::class, 'deposit'])->name('subscription.deposit');
    Route::post('/langganan/pilih', [SubscriptionController::class, 'selectPackage'])->name('subscription.select');
    Route::post('/langganan/batalkan-deposit', [SubscriptionController::class, 'cancelPending'])->name('subscription.cancel-pending');

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
Route::view('/tentang-kami', 'legal.about')->name('legal.about');
Route::view('/syarat-ketentuan', 'legal.terms')->name('legal.terms');
Route::view('/kebijakan-privasi', 'legal.privacy')->name('legal.privacy');
Route::view('/kebijakan-pengembalian-dana', 'legal.refund')->name('legal.refund');

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
    Route::get('/daftar/cek-referral', [RegisterController::class, 'checkReferral'])
        ->middleware('throttle:30,1')
        ->name('register.check-referral');
    Route::get('/lupa-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/lupa-password', [PasswordResetController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])
        ->middleware('throttle:10,1')
        ->name('password.update');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('central.domain')->prefix('email/verifikasi')->group(function () {
    Route::get('/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->whereNumber('id')
        ->middleware(['signed:relative', 'throttle:10,1'])
        ->name('verification.verify');

    Route::middleware(['auth', 'active'])->group(function () {
        Route::get('/', [EmailVerificationController::class, 'notice'])->name('verification.notice');
        Route::post('/kirim-ulang', [EmailVerificationController::class, 'resend'])
            ->middleware('throttle:3,1')
            ->name('verification.send');
        Route::post('/ubah-email', [EmailVerificationController::class, 'changeEmail'])
            ->middleware('throttle:5,1')
            ->name('verification.change-email');
    });
});

/*
| Superadmin
*/
Route::prefix('superadmin')
    ->name('superadmin.')
    ->middleware(['auth', 'active', 'superadmin', 'central.domain'])
    ->group(function () {
        Route::get('/', SuperAdminDashboardController::class)->name('dashboard');
        Route::get('/users', [SuperAdminUsersController::class, 'index'])->name('users');
        Route::put('/tenants/{tenant}', [SuperAdminUsersController::class, 'updateTenant'])->name('tenants.update');
        Route::post('/tenants/{tenant}/toggle', [SuperAdminUsersController::class, 'toggleTenantStatus'])->name('tenants.toggle');
        Route::post('/tenants/{tenant}/inject-saldo', [SuperAdminUsersController::class, 'injectTenantBalance'])->name('tenants.inject-saldo');
        Route::delete('/tenants/{tenant}', [SuperAdminUsersController::class, 'destroyTenant'])->name('tenants.destroy');
        Route::post('/tenants/{tenant}/users', [SuperAdminUsersController::class, 'storeTenantUser'])->name('tenants.users.store');
        Route::put('/users/{user}', [SuperAdminUsersController::class, 'updateUser'])->name('users.update');
        Route::post('/users/{user}/toggle', [SuperAdminUsersController::class, 'toggleUserActive'])->name('users.toggle');
        Route::post('/users/{user}/verify-email', [SuperAdminUsersController::class, 'verifyUserEmail'])->name('users.verify-email');
        Route::delete('/users/{user}', [SuperAdminUsersController::class, 'destroyUser'])->name('users.destroy');

        Route::get('/landing', [SuperAdminLandingController::class, 'edit'])->name('landing');
        Route::post('/landing', [SuperAdminLandingController::class, 'update'])->name('landing.update');

        Route::get('/paket', [SuperAdminSubscriptionPricingController::class, 'edit'])->name('subscription-pricing');
        Route::post('/paket', [SuperAdminSubscriptionPricingController::class, 'update'])->name('subscription-pricing.update');

        Route::get('/pembayaran', [SuperAdminPaymentsController::class, 'index'])->name('payments');
        Route::get('/penarikan', [SuperAdminWithdrawalsController::class, 'index'])->name('withdrawals');
        Route::post('/penarikan/pengaturan', [SuperAdminWithdrawalsController::class, 'updateSettings'])->name('withdrawals.settings');
        Route::post('/penarikan/biaya-voucher', [SuperAdminWithdrawalsController::class, 'updateVoucherFee'])->name('withdrawals.voucher-fee');
        Route::get('/penarikan/rekening/{account}/bukti', [SuperAdminWithdrawalsController::class, 'accountProof'])->name('withdrawals.accounts.proof');
        Route::post('/penarikan/rekening/{account}/verifikasi', [SuperAdminWithdrawalsController::class, 'verifyAccount'])->name('withdrawals.accounts.verify');
        Route::post('/penarikan/rekening/{account}/tolak', [SuperAdminWithdrawalsController::class, 'rejectAccount'])->name('withdrawals.accounts.reject');
        Route::post('/penarikan/{withdrawal}/xendit', [SuperAdminWithdrawalsController::class, 'approveXendit'])->name('withdrawals.xendit');
        Route::post('/penarikan/{withdrawal}/manual', [SuperAdminWithdrawalsController::class, 'markManual'])->name('withdrawals.manual');
        Route::post('/penarikan/{withdrawal}/reject', [SuperAdminWithdrawalsController::class, 'reject'])->name('withdrawals.reject');
        Route::post('/penarikan/{withdrawal}/refresh', [SuperAdminWithdrawalsController::class, 'refresh'])->name('withdrawals.refresh');

        Route::get('/affiliate', [SuperAdminPartnersController::class, 'index'])->name('partners');
        Route::post('/affiliate', [SuperAdminPartnersController::class, 'store'])->name('partners.store');
        Route::post('/affiliate/pengaturan', [SuperAdminPartnersController::class, 'updateSettings'])->name('partners.settings');
        Route::put('/affiliate/{partner}', [SuperAdminPartnersController::class, 'update'])->name('partners.update');
        Route::post('/affiliate/{partner}/setujui', [SuperAdminPartnersController::class, 'approve'])->name('partners.approve');
        Route::post('/affiliate/{partner}/tolak', [SuperAdminPartnersController::class, 'reject'])->name('partners.reject');
        Route::get('/affiliate/{partner}/rekening/bukti', [SuperAdminPartnersController::class, 'bankProof'])->name('partners.bank.proof');
        Route::get('/affiliate/{partner}/rekening/ktp', [SuperAdminPartnersController::class, 'ktp'])->name('partners.bank.ktp');
        Route::post('/affiliate/{partner}/rekening/verifikasi', [SuperAdminPartnersController::class, 'verifyBank'])->name('partners.bank.verify');
        Route::post('/affiliate/{partner}/rekening/tolak', [SuperAdminPartnersController::class, 'rejectBank'])->name('partners.bank.reject');
        Route::post('/affiliate/penarikan/kirim-semua', [SuperAdminPartnersController::class, 'sendAllXendit'])->name('partners.withdrawals.send-all');
        Route::post('/affiliate/penarikan/{withdrawal}/xendit', [SuperAdminPartnersController::class, 'sendXendit'])->name('partners.withdrawals.xendit');
        Route::post('/affiliate/penarikan/{withdrawal}/cek-status', [SuperAdminPartnersController::class, 'refreshWithdrawal'])->name('partners.withdrawals.refresh');
        Route::post('/affiliate/penarikan/{withdrawal}/selesai', [SuperAdminPartnersController::class, 'completeWithdrawal'])->name('partners.withdrawals.complete');
        Route::post('/affiliate/penarikan/{withdrawal}/tolak', [SuperAdminPartnersController::class, 'rejectWithdrawal'])->name('partners.withdrawals.reject');
        Route::post('/affiliate/komisi/{commission}/batal', [SuperAdminPartnersController::class, 'cancelCommission'])->name('partners.commissions.cancel');
    });

/*
| Program Affiliate (central domain)
*/
Route::middleware('central.domain')->group(function () {
    Route::get('/r/{code}', [PartnerPublicController::class, 'shortLink'])
        ->where('code', '[A-Za-z0-9]{3,20}')
        ->name('partner.short');
    Route::permanentRedirect('/mitra', '/affiliate');
    Route::get('/mitra/{path}', fn (string $path) => redirect('/affiliate/'.$path, 301))->where('path', '.*');
    Route::get('/affiliate', [PartnerPublicController::class, 'landing'])->name('partner.landing');

    Route::middleware('guest')->group(function () {
        Route::get('/affiliate/daftar', [PartnerPublicController::class, 'create'])->name('partner.register');
        Route::post('/affiliate/daftar', [PartnerPublicController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('partner.register.store');
    });

    Route::prefix('affiliate')
        ->name('partner.')
        ->middleware(['auth', 'active', 'verified.email', 'partner'])
        ->group(function () {
            Route::get('/dashboard', [PartnerPanelController::class, 'dashboard'])->name('dashboard');
            Route::get('/rujukan', [PartnerPanelController::class, 'referrals'])->name('referrals');
            Route::get('/komisi', [PartnerPanelController::class, 'commissions'])->name('commissions');
            Route::get('/penarikan', [PartnerPanelController::class, 'withdrawals'])->name('withdrawals');
            Route::post('/penarikan', [PartnerPanelController::class, 'withdraw'])
                ->middleware('throttle:10,1')
                ->name('withdrawals.store');
            Route::post('/penarikan/rekening', [PartnerPanelController::class, 'saveBank'])
                ->middleware('throttle:10,1')
                ->name('withdrawals.bank');
            Route::get('/penarikan/rekening/bukti', [PartnerPanelController::class, 'bankProof'])->name('withdrawals.bank.proof');
            Route::get('/penarikan/rekening/ktp', [PartnerPanelController::class, 'ktp'])->name('withdrawals.bank.ktp');
            Route::post('/penarikan/{withdrawal}/batal', [PartnerPanelController::class, 'cancelWithdrawal'])->name('withdrawals.cancel');
        });
});

/*
| ISP Panel
*/
Route::middleware(['auth', 'active', 'verified.email', 'tenant', 'tenant.membership', 'tenant.host', 'tenant.subscription'])
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

        Route::middleware(['auth', 'active', 'verified.email', 'tenant'])->group(function () {
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

        Route::get('/voucher', [VoucherShopController::class, 'index'])->name('voucher-shop.index');
        Route::post('/voucher', [VoucherShopController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('voucher-shop.store');
        Route::get('/voucher/o/{token}', [VoucherShopController::class, 'show'])
            ->where('token', '[A-Za-z0-9]{40}')
            ->name('voucher-shop.show');
        Route::get('/voucher/o/{token}/status', [VoucherShopController::class, 'status'])
            ->where('token', '[A-Za-z0-9]{40}')
            ->middleware('throttle:90,1')
            ->name('voucher-shop.status');
    });

/*
| Client tenant panel
*/
Route::domain('{tenant}.'.config('mikpay.tenant_base_domain'))
    ->where(['tenant' => $tenantSubPattern])
    ->middleware(['auth', 'active', 'verified.email', 'tenant', 'tenant.membership', 'tenant.subscription'])
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