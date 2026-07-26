<?php

namespace Tests\Feature;

use App\Models\BillingPayment;
use App\Models\CashTransaction;
use App\Models\MikpayRouter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenantHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $name, string $email, string $username): User
    {
        return User::factory()->create([
            'name' => $name,
            'email' => $email,
            'username' => $username,
            'password' => 'password',
            'role' => 'user',
            'is_super_admin' => false,
            'status' => 'active',
            'subscription_status' => 'active',
            'subscription_end' => now()->addYear()->toDateString(),
        ]);
    }

    private function makeRouter(User $user, string $sessionName): MikpayRouter
    {
        return MikpayRouter::withoutGlobalScopes()->create([
            'user_id' => $user->id,
            'session_name' => $sessionName,
            'host' => '127.0.0.1:8728',
            'username' => 'admin',
            'password' => base64_encode('admin'),
            'router_name' => 'Router ' . $sessionName,
            'status' => 'enable',
        ]);
    }

    public function test_user_cannot_connect_to_router_owned_by_other_user(): void
    {
        $userA = $this->makeUser('User A', 'a@example.test', 'user-a');
        $userB = $this->makeUser('User B', 'b@example.test', 'user-b');
        $routerB = $this->makeRouter($userB, 'router-b');

        $response = $this->actingAs($userA)->post(route('sessions.connect'), [
            'session' => $routerB->session_name,
        ]);

        $response->assertRedirect(route('sessions'));
        $response->assertSessionHas('error', 'Router tidak ditemukan.');
    }

    public function test_cashflow_list_only_shows_selected_router_data(): void
    {
        $user = $this->makeUser('User A', 'cash@example.test', 'cash-a');
        $routerA = $this->makeRouter($user, 'router-a');
        $routerB = $this->makeRouter($user, 'router-b');

        CashTransaction::create([
            'tanggal' => '2026-03-01',
            'tipe' => 'masuk',
            'kategori' => 'lainnya',
            'nominal' => 11000,
            'metode' => 'tunai',
            'keterangan' => 'TENANT-A-ONLY',
            'router_session' => $routerA->session_name,
        ]);
        CashTransaction::create([
            'tanggal' => '2026-03-01',
            'tipe' => 'masuk',
            'kategori' => 'lainnya',
            'nominal' => 22000,
            'metode' => 'tunai',
            'keterangan' => 'TENANT-B-HIDDEN',
            'router_session' => $routerB->session_name,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['selected_router' => $routerA->session_name])
            ->get(route('keuangan.cashflow'));

        $response->assertOk();
        $response->assertSee('TENANT-A-ONLY');
        $response->assertDontSee('TENANT-B-HIDDEN');
    }

    public function test_edit_cashflow_for_other_router_is_forbidden(): void
    {
        $user = $this->makeUser('User A', 'edit@example.test', 'edit-a');
        $routerA = $this->makeRouter($user, 'router-a-2');
        $routerB = $this->makeRouter($user, 'router-b-2');

        $cashflow = CashTransaction::create([
            'tanggal' => '2026-03-02',
            'tipe' => 'keluar',
            'kategori' => 'listrik',
            'nominal' => 50000,
            'metode' => 'tunai',
            'keterangan' => 'ROUTER-B-RECORD',
            'router_session' => $routerB->session_name,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['selected_router' => $routerA->session_name])
            ->get(route('keuangan.cashflow.edit', $cashflow));

        $response->assertForbidden();
    }

    public function test_store_cashflow_persists_selected_router_session(): void
    {
        $user = $this->makeUser('User A', 'report@example.test', 'report-a');
        $routerA = $this->makeRouter($user, 'router-a-3');
        $response = $this->actingAs($user)
            ->withSession(['selected_router' => $routerA->session_name])
            ->post(route('keuangan.cashflow.store'), [
                'tanggal' => '2026-03-10',
                'tipe' => 'masuk',
                'kategori' => 'lainnya',
                'nominal' => 12345,
                'metode' => 'tunai',
                'keterangan' => 'ROUTER-SESSION-CHECK',
            ]);

        $response->assertRedirect(route('keuangan.cashflow'));
        $this->assertDatabaseHas('cash_transactions', [
            'keterangan' => 'ROUTER-SESSION-CHECK',
            'router_session' => $routerA->session_name,
        ]);
    }

    public function test_user_cannot_set_telegram_router_owned_by_other_user(): void
    {
        $userA = $this->makeUser('User A', 'tg-a@example.test', 'tg-a');
        $userB = $this->makeUser('User B', 'tg-b@example.test', 'tg-b');
        $routerB = $this->makeRouter($userB, 'router-tg-b');

        $response = $this->actingAs($userA)->post(route('sessions.telegram.update'), [
            'telegram_bot_token' => '123:abc',
            'telegram_chat_id' => '123456789',
            'telegram_router_session' => $routerB->session_name,
            'telegram_enabled' => '1',
            'telegram_notify_connection' => '1',
        ]);

        $response->assertRedirect(route('sessions'));
        $response->assertSessionHas('error', 'Router Telegram tidak ditemukan.');
    }

    public function test_print_invoice_for_other_router_is_forbidden(): void
    {
        $user = $this->makeUser('User A', 'inv@example.test', 'inv-a');
        $routerA = $this->makeRouter($user, 'router-inv-a');
        $routerB = $this->makeRouter($user, 'router-inv-b');

        $payment = BillingPayment::create([
            'customer_name' => 'cust-x',
            'display_name' => 'Cust X',
            'phone' => '08123',
            'amount' => 50000,
            'period' => '2026-03',
            'payment_date' => '2026-03-20 10:00:00',
            'payment_method' => 'tunai',
            'notes' => null,
            'status' => 'paid',
            'router_session' => $routerB->session_name,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['selected_router' => $routerA->session_name])
            ->get(route('ppp.billing.invoice.print', $payment));

        $response->assertForbidden();
    }

    public function test_destroy_payment_for_other_router_is_forbidden(): void
    {
        $user = $this->makeUser('User A', 'del@example.test', 'del-a');
        $routerA = $this->makeRouter($user, 'router-del-a');
        $routerB = $this->makeRouter($user, 'router-del-b');

        $payment = BillingPayment::create([
            'customer_name' => 'cust-y',
            'display_name' => 'Cust Y',
            'phone' => '08124',
            'amount' => 75000,
            'period' => '2026-03',
            'payment_date' => '2026-03-21 10:00:00',
            'payment_method' => 'transfer',
            'notes' => null,
            'status' => 'paid',
            'router_session' => $routerB->session_name,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['selected_router' => $routerA->session_name])
            ->delete(route('ppp.billing.payments.destroy', $payment));

        $response->assertForbidden();
        $this->assertDatabaseHas('billing_payments', ['id' => $payment->id]);
    }

    public function test_sessions_page_only_lists_own_routers(): void
    {
        $userA = $this->makeUser('User A', 'sess-a@test', 'sess-a');
        $userB = $this->makeUser('User B', 'sess-b@test', 'sess-b');
        $this->makeRouter($userA, 'own-router-a');
        $this->makeRouter($userB, 'other-router-b');

        $response = $this->actingAs($userA)->get(route('sessions'));

        $response->assertOk();
        $response->assertSee('own-router-a');
        $response->assertDontSee('other-router-b');
    }

    public function test_subscription_page_only_lists_own_routers(): void
    {
        $userA = $this->makeUser('User A', 'sub-a@test', 'sub-a');
        $userB = $this->makeUser('User B', 'sub-b@test', 'sub-b');
        $this->makeRouter($userA, 'router-only-a');
        $this->makeRouter($userB, 'router-only-b');

        $response = $this->actingAs($userA)->get(route('langganan'));

        $response->assertOk();
        $response->assertSee('router-only-a');
        $response->assertDontSee('router-only-b');
    }
}

