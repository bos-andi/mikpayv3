<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SuperAdminLoginController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\HotspotController;
use App\Http\Controllers\PppController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RouterController;
use App\Http\Controllers\SessionsController;
use App\Http\Controllers\SuperAdminPaymentController;
use App\Http\Controllers\SuperAdminSubscriptionController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

// Landing: resources/views/index.blade.php (LandingController)
Route::get('/', [LandingController::class, 'index'])->name('home');
Route::get('/index', [LandingController::class, 'index'])->name('index');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

// Login Super Admin — halaman & desain terpisah (tanpa middleware guest agar redirect dari controller bisa fleksibel)
Route::prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/login', [SuperAdminLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [SuperAdminLoginController::class, 'login'])->name('login.perform');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

Route::middleware(['auth', 'account.active'])->group(function () {
    // Langganan — tetap bisa diakses jika trial/langganan habis (perpanjang / bayar).
    Route::get('/langganan', [SubscriptionController::class, 'index'])->name('langganan');
    Route::post('/langganan/pilih-paket', [SubscriptionController::class, 'selectPackage'])->name('langganan.pilih-paket');
    Route::post('/langganan/batal-checkout', [SubscriptionController::class, 'cancelCheckout'])->name('langganan.batal-checkout');
    Route::get('/langganan/pembayaran', [SubscriptionController::class, 'checkout'])->name('langganan.pembayaran');
    Route::post('/langganan/request', [SubscriptionController::class, 'requestPayment'])->name('langganan.request');
});

Route::middleware(['auth', 'account.active', 'subscription.active'])->group(function () {
    // Sessions = halaman pertama setelah login (pilih/kelola router), sesuai template admin.php?id=sessions
    Route::get('/sessions', [SessionsController::class, 'index'])->name('sessions');
    Route::get('/sessions/fonnte', [SessionsController::class, 'fonnte'])->name('sessions.fonnte');
    Route::post('/sessions/fonnte', [SessionsController::class, 'updateFonnte'])->name('sessions.fonnte.update');
    Route::post('/sessions/fonnte/test', [SessionsController::class, 'testFonnte'])->name('sessions.fonnte.test');
    Route::post('/sessions/account', [SessionsController::class, 'updateAccount'])->name('sessions.account.update');
    Route::post('/sessions/telegram', [SessionsController::class, 'updateTelegram'])->name('sessions.telegram.update');
    Route::post('/sessions/telegram/test', [SessionsController::class, 'testTelegram'])->name('sessions.telegram.test');
    Route::post('/sessions/connect', [SessionsController::class, 'connect'])->name('sessions.connect');
    Route::post('/sessions/disconnect', [SessionsController::class, 'disconnect'])->name('sessions.disconnect');

    // Admin Settings tools (template parity)
    Route::get('/admin/upload-logo', [AdminSettingsController::class, 'uploadLogo'])->name('admin.upload-logo');
    Route::post('/admin/upload-logo', [AdminSettingsController::class, 'uploadLogoStore'])->name('admin.upload-logo.store');
    Route::delete('/admin/upload-logo/{filename}', [AdminSettingsController::class, 'uploadLogoDelete'])->name('admin.upload-logo.delete');

    Route::get('/admin/template-editor', [AdminSettingsController::class, 'templateEditor'])->name('admin.template-editor');
    Route::post('/admin/template-editor', [AdminSettingsController::class, 'templateEditorSave'])->name('admin.template-editor.save');
    Route::post('/admin/template-editor/preview', [AdminSettingsController::class, 'templateEditorPreview'])->name('admin.template-editor.preview');

    Route::post('/admin/business-settings', [AdminSettingsController::class, 'updateBusinessSettings'])->name('admin.business-settings.update');

    // Router: tambah & edit (sesuai template Add Router + id=settings&session=XXX)
    Route::get('/routers/create', [RouterController::class, 'create'])->name('routers.create');
    Route::post('/routers', [RouterController::class, 'store'])->name('routers.store');
    Route::get('/routers/{router}/edit', [RouterController::class, 'edit'])->name('routers.edit');
    Route::put('/routers/{router}', [RouterController::class, 'update'])->name('routers.update');
    Route::delete('/routers/{router}', [RouterController::class, 'destroy'])->name('routers.destroy');

    // Dashboard = setelah pilih router (index.php?session=XXX)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Hotspot submenu (butuh selected_router)
    Route::get('/hotspot/users', [HotspotController::class, 'users'])->name('hotspot.users');
    Route::get('/hotspot/users/remove/{id}', [HotspotController::class, 'removeUser'])->name('hotspot.users.remove');
    Route::get('/hotspot/users/enable/{id}', [HotspotController::class, 'enableUser'])->name('hotspot.users.enable');
    Route::get('/hotspot/users/disable/{id}', [HotspotController::class, 'disableUser'])->name('hotspot.users.disable');
    Route::get('/hotspot/users/remove-by-comment', [HotspotController::class, 'removeByComment'])->name('hotspot.users.remove-by-comment');
    Route::get('/hotspot/users/remove-expired', [HotspotController::class, 'removeExpired'])->name('hotspot.users.remove-expired');
    Route::get('/hotspot/voucher/print', [HotspotController::class, 'printVoucher'])->name('hotspot.voucher.print');
    Route::get('/hotspot/add-user', [HotspotController::class, 'addUser'])->name('hotspot.add-user');
    Route::post('/hotspot/add-user', [HotspotController::class, 'storeAddUser'])->name('hotspot.add-user.store');
    Route::get('/hotspot/generate-user', [HotspotController::class, 'generateUser'])->name('hotspot.generate-user');
    Route::post('/hotspot/generate-user', [HotspotController::class, 'storeGenerateUser'])->name('hotspot.generate-user.store');
    Route::get('/hotspot/user-profiles', [HotspotController::class, 'userProfiles'])->name('hotspot.user-profiles');
    Route::get('/hotspot/user-profiles/add', [HotspotController::class, 'addUserProfile'])->name('hotspot.user-profiles.add');
    Route::post('/hotspot/user-profiles', [HotspotController::class, 'storeUserProfile'])->name('hotspot.user-profiles.store');
    Route::get('/hotspot/active', [HotspotController::class, 'active'])->name('hotspot.active');
    Route::get('/hotspot/hosts', [HotspotController::class, 'hosts'])->name('hotspot.hosts');
    Route::get('/hotspot/ipbinding', [HotspotController::class, 'ipbinding'])->name('hotspot.ipbinding');
    Route::get('/hotspot/cookies', [HotspotController::class, 'cookies'])->name('hotspot.cookies');

    Route::get('/report/selling', [ReportController::class, 'selling'])->name('report.selling');

    // Keuangan
    Route::get('/keuangan/cashflow', [FinanceController::class, 'cashflow'])->name('keuangan.cashflow');
    Route::post('/keuangan/cashflow', [FinanceController::class, 'storeCashflow'])->name('keuangan.cashflow.store');
    Route::get('/keuangan/cashflow/{cashflow}/edit', [FinanceController::class, 'editCashflow'])->name('keuangan.cashflow.edit');
    Route::put('/keuangan/cashflow/{cashflow}', [FinanceController::class, 'updateCashflow'])->name('keuangan.cashflow.update');
    Route::delete('/keuangan/cashflow/{cashflow}', [FinanceController::class, 'destroyCashflow'])->name('keuangan.cashflow.destroy');
    Route::get('/keuangan/report', [FinanceController::class, 'report'])->name('keuangan.report');
    Route::get('/keuangan/report/pdf', [FinanceController::class, 'reportPdf'])->name('keuangan.report.pdf');

    // PPP submenu (butuh selected_router)
    Route::get('/ppp/secrets', [PppController::class, 'secrets'])->name('ppp.secrets');
    Route::post('/ppp/secrets', [PppController::class, 'storeSecret'])->name('ppp.store-secret');
    Route::put('/ppp/secrets/{id}', [PppController::class, 'updateSecret'])->name('ppp.update-secret');
    Route::delete('/ppp/secrets/{id}', [PppController::class, 'destroySecret'])->name('ppp.destroy-secret');
    Route::get('/ppp/profiles', [PppController::class, 'profiles'])->name('ppp.profiles');
    Route::post('/ppp/profiles', [PppController::class, 'storeProfile'])->name('ppp.store-profile');
    Route::get('/ppp/active', [PppController::class, 'active'])->name('ppp.active');
    Route::get('/ppp/billing', [PppController::class, 'billing'])->name('ppp.billing');
    Route::get('/ppp/billing/report/pdf', [PppController::class, 'exportBillingReportPdf'])->name('ppp.billing.report.pdf');
    Route::get('/ppp/billing/template/export', [PppController::class, 'exportBillingTemplate'])->name('ppp.billing.template.export');
    Route::post('/ppp/billing/template/import', [PppController::class, 'importBillingTemplate'])->name('ppp.billing.template.import');
    Route::get('/ppp/billing/customers/create', [PppController::class, 'createCustomer'])->name('ppp.billing.customers.create');
    Route::post('/ppp/billing/customers', [PppController::class, 'storeCustomer'])->name('ppp.billing.customers.store');
    Route::post('/ppp/billing/period-promise', [PppController::class, 'storePeriodPromise'])->name('ppp.billing.period-promise.store');
    Route::get('/ppp/billing/customers/edit', [PppController::class, 'editCustomer'])->name('ppp.billing.customers.edit');
    Route::post('/ppp/billing/payments', [PppController::class, 'storePayment'])->name('ppp.billing.payments.store');
    Route::delete('/ppp/billing/payments/{payment}', [PppController::class, 'destroyPayment'])->name('ppp.billing.payments.destroy');
    Route::get('/ppp/billing/invoice/{payment}/print', [PppController::class, 'printInvoice'])->name('ppp.billing.invoice.print');
});


// Tanpa middleware auth: biar guest diarahkan ke superadmin/login (bukan /login). Cek login + role ada di SuperAdminMiddleware.
Route::prefix('superadmin')->name('superadmin.')->middleware(['superadmin'])->group(function () {
    Route::post('/logout', [SuperAdminLoginController::class, 'logout'])->name('logout');
    Route::get('/', [SuperAdminPaymentController::class, 'dashboard'])->name('dashboard');
    Route::get('/payments', [SuperAdminPaymentController::class, 'payments'])->name('payments');
    Route::get('/users', [SuperAdminPaymentController::class, 'users'])->name('users');
    Route::post('/users/{user}/expire-subscription', [SuperAdminPaymentController::class, 'expireSubscription'])->name('users.expire-subscription');
    Route::post('/users/{user}/deactivate', [SuperAdminPaymentController::class, 'deactivateUser'])->name('users.deactivate');
    Route::post('/users/{user}/activate', [SuperAdminPaymentController::class, 'activateUser'])->name('users.activate');
    Route::post('/payments/{payment}/approve', [SuperAdminPaymentController::class, 'approve'])->name('payments.approve');
    Route::post('/payments/{payment}/reject', [SuperAdminPaymentController::class, 'reject'])->name('payments.reject');
    Route::post('/users/{user}/trial', [SuperAdminPaymentController::class, 'setTrial'])->name('users.trial');
    Route::get('/subscription-pricing', [SuperAdminSubscriptionController::class, 'edit'])->name('subscription-pricing');
    Route::post('/subscription-pricing', [SuperAdminSubscriptionController::class, 'update'])->name('subscription-pricing.update');
});

