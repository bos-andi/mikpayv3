<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_payment_user_cannot_open_sessions_but_can_langganan(): void
    {
        $user = User::factory()->create([
            'username' => 'pending-user',
            'subscription_status' => 'pending_payment',
            'subscription_end' => null,
            'status' => 'active',
        ]);

        $this->actingAs($user)->get(route('sessions'))->assertRedirect(route('langganan'));

        $this->actingAs($user)->get(route('langganan'))->assertOk();
    }

    public function test_expired_subscription_redirects_to_langganan(): void
    {
        $user = User::factory()->create([
            'username' => 'expired-user',
            'subscription_status' => 'active',
            'subscription_end' => now()->subDays(3)->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($user)->get(route('sessions'))->assertRedirect(route('langganan'));
    }

    public function test_active_subscription_until_end_of_end_date_day(): void
    {
        $user = User::factory()->create([
            'username' => 'last-day-user',
            'subscription_status' => 'active',
            'subscription_end' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($user)->get(route('sessions'))->assertOk();
    }

    public function test_trial_user_with_future_end_can_access_sessions(): void
    {
        $user = User::factory()->create([
            'username' => 'trial-user',
            'subscription_status' => 'trial',
            'subscription_package' => 'trial_5_days',
            'subscription_start' => now()->toDateString(),
            'subscription_end' => now()->addDays(5)->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($user)->get(route('sessions'))->assertOk();
    }

    public function test_trial_user_past_end_cannot_access_sessions(): void
    {
        $user = User::factory()->create([
            'username' => 'trial-expired',
            'subscription_status' => 'trial',
            'subscription_package' => 'trial_5_days',
            'subscription_start' => now()->subDays(10)->toDateString(),
            'subscription_end' => now()->subDays(1)->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($user)->get(route('sessions'))->assertRedirect(route('langganan'));
    }

    public function test_legacy_user_without_subscription_fields_can_access_sessions(): void
    {
        $user = User::factory()->create([
            'username' => 'legacy-user',
            'subscription_status' => null,
            'subscription_end' => null,
            'status' => 'active',
        ]);

        $this->actingAs($user)->get(route('sessions'))->assertOk();
    }

    public function test_inactive_user_redirected_to_login_from_langganan(): void
    {
        $user = User::factory()->create([
            'username' => 'inactive-user',
            'status' => 'inactive',
            'subscription_status' => 'active',
            'subscription_end' => now()->addYear()->toDateString(),
        ]);

        $this->actingAs($user)->get(route('langganan'))->assertRedirect(route('login'));
    }
}
