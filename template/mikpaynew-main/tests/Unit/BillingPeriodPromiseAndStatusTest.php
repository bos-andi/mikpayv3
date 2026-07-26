<?php

namespace Tests\Unit;

use App\Helpers\BillingStatusHelper;
use App\Models\BillingPayment;
use App\Models\BillingPeriodPromise;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingPeriodPromiseAndStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_status_uses_promise_date_for_days_to_due(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-10 12:00:00', 'UTC'));
        $st = BillingStatusHelper::getStatus('u1', 10, '2026-04-15');
        $this->assertSame('waiting', $st['status']);
        // Carbon diffInDays(today noon → promised startOfDay) = 4 for 10th→15th
        $this->assertSame(4, $st['days_to_due']);
        $this->assertTrue($st['is_promised']);
        $this->assertSame('2026-04-15', $st['promised_pay_date']);
    }

    public function test_status_without_promise_due_today_on_due_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-10 12:00:00', 'UTC'));
        $st = BillingStatusHelper::getStatus('u1', 10, null);
        $this->assertSame('due_today', $st['status']);
        $this->assertSame(0, $st['days_to_due']);
        $this->assertFalse($st['is_promised']);
    }

    public function test_paid_ignores_promise_flags(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-10 12:00:00', 'UTC'));
        $period = '2026-04';
        BillingPayment::create([
            'customer_name' => 'u1',
            'amount' => 100000,
            'remaining_amount' => 0,
            'period' => $period,
            'payment_date' => '2026-04-09',
            'status' => 'paid',
            'router_session' => 'r1',
        ]);
        $st = BillingStatusHelper::getStatus('u1', 10, '2026-04-20');
        $this->assertSame('paid', $st['status']);
        $this->assertFalse($st['is_promised']);
        $this->assertNull($st['promised_pay_date']);
    }

    public function test_billing_period_promise_can_be_persisted(): void
    {
        BillingPeriodPromise::create([
            'customer_name' => 'u1',
            'router_session' => 'r1',
            'period' => '2026-04',
            'promised_pay_date' => '2026-04-15',
        ]);
        $this->assertDatabaseCount('billing_period_promises', 1);
        $this->assertDatabaseHas('billing_period_promises', [
            'customer_name' => 'u1',
            'router_session' => 'r1',
            'period' => '2026-04',
        ]);
    }

    public function test_billing_period_promise_unique_per_customer_router_period(): void
    {
        BillingPeriodPromise::create([
            'customer_name' => 'u1',
            'router_session' => 'r1',
            'period' => '2026-04',
            'promised_pay_date' => '2026-04-15',
        ]);
        $this->expectException(QueryException::class);
        BillingPeriodPromise::create([
            'customer_name' => 'u1',
            'router_session' => 'r1',
            'period' => '2026-04',
            'promised_pay_date' => '2026-04-18',
        ]);
    }
}
