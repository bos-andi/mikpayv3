<?php

namespace Tests\Unit;

use App\Models\BillingCustomer;
use App\Models\BillingPeriodPromise;
use App\Models\FonnteReminderDispatch;
use App\Models\FonnteSetting;
use App\Models\MikpayRouter;
use App\Models\User;
use App\Services\FonnteApiService;
use App\Services\FonnteBillingReminderService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FonnteBillingReminderPromiseRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * @return array{user: User, router: MikpayRouter, fonnte: FonnteSetting}
     */
    private function seedTenant(string $sessionName): array
    {
        $user = User::factory()->create(['status' => 'active']);
        $router = MikpayRouter::withoutGlobalScope('owner')->create([
            'user_id' => $user->id,
            'session_name' => $sessionName,
            'host' => '192.168.1.1:8728',
            'username' => 'u',
            'password' => base64_encode('p'),
            'status' => 'enable',
        ]);
        $defaults = FonnteSetting::defaultTemplatePayload();
        $fonnte = FonnteSetting::create(array_merge($defaults, [
            'user_id' => $user->id,
            'enabled' => true,
            'api_token' => 'test-token',
            'auto_send_h3' => true,
            'auto_send_h0' => true,
            'auto_send_overdue' => true,
            'send_window_start' => '06:00',
            'send_window_end' => '22:00',
        ]));

        return ['user' => $user, 'router' => $router, 'fonnte' => $fonnte];
    }

    public function test_janji_bayar_tidak_kirim_h3_sebelum_hari_janji(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-12 10:00:00', 'UTC'));
        extract($this->seedTenant('r-fonnte-j1'));

        BillingCustomer::create([
            'customer_name' => 'ppp_janji_h3skip',
            'due_day' => 5,
            'monthly_fee' => 100000,
            'phone' => '081234567890',
            'router_session' => $router->session_name,
        ]);
        BillingPeriodPromise::create([
            'customer_name' => 'ppp_janji_h3skip',
            'router_session' => $router->session_name,
            'period' => '2026-04',
            'promised_pay_date' => '2026-04-15',
        ]);

        $this->mock(FonnteApiService::class, function ($m) {
            $m->shouldNotReceive('sendText');
        });
        $batch = app(FonnteBillingReminderService::class)->runForTenant($fonnte, $user, false);
        $this->assertSame(0, $batch['sent']);
    }

    public function test_janji_bayar_kirim_h0_hanya_di_hari_janji(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-15 10:00:00', 'UTC'));
        extract($this->seedTenant('r-fonnte-j2'));

        BillingCustomer::create([
            'customer_name' => 'ppp_janji_h0day',
            'due_day' => 5,
            'monthly_fee' => 100000,
            'phone' => '081234567890',
            'router_session' => $router->session_name,
        ]);
        BillingPeriodPromise::create([
            'customer_name' => 'ppp_janji_h0day',
            'router_session' => $router->session_name,
            'period' => '2026-04',
            'promised_pay_date' => '2026-04-15',
        ]);

        $this->mock(FonnteApiService::class, function ($m) {
            $m->shouldReceive('sendText')->once()->andReturn(['ok' => true, 'body' => 'ok']);
        });
        $batch = app(FonnteBillingReminderService::class)->runForTenant($fonnte, $user, false);
        $this->assertSame(1, $batch['sent']);
        $this->assertDatabaseHas('fonnte_reminder_dispatches', [
            'user_id' => $user->id,
            'customer_name' => 'ppp_janji_h0day',
            'kind' => 'h0',
        ]);
    }

    public function test_janji_bayar_tidak_kirim_overdue_setelah_lewat_janji(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-16 10:00:00', 'UTC'));
        extract($this->seedTenant('r-fonnte-j3'));

        BillingCustomer::create([
            'customer_name' => 'ppp_janji_no_od',
            'due_day' => 5,
            'monthly_fee' => 100000,
            'phone' => '081234567890',
            'router_session' => $router->session_name,
        ]);
        BillingPeriodPromise::create([
            'customer_name' => 'ppp_janji_no_od',
            'router_session' => $router->session_name,
            'period' => '2026-04',
            'promised_pay_date' => '2026-04-15',
        ]);

        $this->mock(FonnteApiService::class, function ($m) {
            $m->shouldNotReceive('sendText');
        });
        $batch = app(FonnteBillingReminderService::class)->runForTenant($fonnte, $user, false);
        $this->assertSame(0, $batch['sent']);
    }

    public function test_tanpa_janji_masih_kirim_h3(): void
    {
        // H-3 membutuhkan days_to_due === 3 (lihat BillingStatusHelper + diffInDays Carbon).
        Carbon::setTestNow(Carbon::parse('2026-04-16 10:00:00', 'UTC'));
        extract($this->seedTenant('r-fonnte-np'));

        BillingCustomer::create([
            'customer_name' => 'ppp_norm_h3',
            'due_day' => 20,
            'monthly_fee' => 100000,
            'phone' => '081298765432',
            'router_session' => $router->session_name,
        ]);

        $this->mock(FonnteApiService::class, function ($m) {
            $m->shouldReceive('sendText')->once()->andReturn(['ok' => true, 'body' => 'ok']);
        });
        $batch = app(FonnteBillingReminderService::class)->runForTenant($fonnte, $user, false);
        $this->assertSame(1, $batch['sent']);
        $this->assertDatabaseHas('fonnte_reminder_dispatches', [
            'user_id' => $user->id,
            'customer_name' => 'ppp_norm_h3',
            'kind' => 'h3',
        ]);
    }

    public function test_tanpa_janji_masih_kirim_overdue(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-22 10:00:00', 'UTC'));
        extract($this->seedTenant('r-fonnte-od'));

        BillingCustomer::create([
            'customer_name' => 'ppp_norm_od',
            'due_day' => 20,
            'monthly_fee' => 100000,
            'phone' => '0812111222333',
            'router_session' => $router->session_name,
        ]);

        $this->mock(FonnteApiService::class, function ($m) {
            $m->shouldReceive('sendText')->once()->andReturn(['ok' => true, 'body' => 'ok']);
        });
        $batch = app(FonnteBillingReminderService::class)->runForTenant($fonnte, $user, false);
        $this->assertSame(1, $batch['sent']);
        $this->assertDatabaseHas('fonnte_reminder_dispatches', [
            'user_id' => $user->id,
            'customer_name' => 'ppp_norm_od',
            'kind' => 'overdue',
        ]);
    }
}
