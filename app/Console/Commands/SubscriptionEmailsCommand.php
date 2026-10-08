<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\SubscriptionExpiredNotification;
use App\Notifications\SubscriptionExpiringNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SubscriptionEmailsCommand extends Command
{
    protected $signature = 'mikpay:subscription-emails {--dry-run : Simulasi tanpa kirim email}';

    protected $description = 'Kirim email pengingat langganan/trial MikPay yang akan & sudah berakhir';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        if ($dry) {
            $this->warn('Mode dry-run: tidak ada email yang dikirim.');
        }

        $now = wib_now();
        $trialHours = max(1, (int) config('mikpay.subscription_emails.trial_remind_hours', 24));
        $paidHours = max(1, (int) config('mikpay.subscription_emails.paid_remind_hours', 72));
        $expiredLookbackDays = max(1, (int) config('mikpay.subscription_emails.expired_lookback_days', 7));
        $sent = ['expiring' => 0, 'expired' => 0];

        Tenant::query()
            ->where('plan', '!=', 'tunnel')
            ->where('status', '!=', 'inactive')
            ->orderBy('id')
            ->chunkById(100, function ($tenants) use ($now, $trialHours, $paidHours, $expiredLookbackDays, $dry, &$sent) {
                foreach ($tenants as $tenant) {
                    if ($tenant->awaitingEmailVerification()) {
                        continue;
                    }
                    $end = $tenant->subscriptionEndsAt();
                    if ($end === null) {
                        continue;
                    }

                    $key = $end->toIso8601String();
                    $remindHours = ($tenant->plan ?? '') === 'trial' ? $trialHours : $paidHours;

                    if ($end->greaterThan($now)) {
                        if ($end->greaterThan($now->copy()->addHours($remindHours))
                            || $tenant->metaGet('notifications.expiring_for') === $key) {
                            continue;
                        }
                        $kind = 'expiring';
                    } else {
                        if ($end->lessThan($now->copy()->subDays($expiredLookbackDays))
                            || $tenant->metaGet('notifications.expired_for') === $key) {
                            continue;
                        }
                        $kind = 'expired';
                    }

                    $recipients = User::query()
                        ->where('tenant_id', $tenant->id)
                        ->where('role', User::ROLE_TENANT_ADMIN)
                        ->where('is_active', true)
                        ->get();
                    if ($recipients->isEmpty()) {
                        continue;
                    }

                    $this->line(sprintf('[%s] %s → %s (berakhir %s)',
                        $kind, $tenant->subdomain, $recipients->pluck('email')->implode(', '), format_wib($end)));

                    if ($dry) {
                        continue;
                    }

                    Notification::send($recipients, $kind === 'expiring'
                        ? new SubscriptionExpiringNotification($tenant)
                        : new SubscriptionExpiredNotification($tenant));

                    $tenant->metaSet("notifications.{$kind}_for", $key);
                    $tenant->save();
                    $sent[$kind]++;
                }
            });

        $this->info("Email terkirim: {$sent['expiring']} akan berakhir, {$sent['expired']} sudah berakhir.");
        if (! $dry && array_sum($sent) > 0) {
            Log::info('mikpay.subscription-emails', $sent);
        }

        return self::SUCCESS;
    }
}
