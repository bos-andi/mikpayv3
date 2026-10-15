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
        $daysBefore = max(1, (int) config('mikpay.subscription_emails.remind_days_before', 3));
        $sendAt = $now->copy()->setTime(min(23, max(0, (int) config('mikpay.subscription_emails.send_hour', 7))), 0);
        // Trial yang lebih pendek dari H-N sudah masuk jendela pengingat sejak hari daftar.
        $trialGetsEarlyReminder = (int) config('mikpay.trial_days', 3) > $daysBefore;
        $expiredLookbackDays = max(1, (int) config('mikpay.subscription_emails.expired_lookback_days', 7));
        $sent = ['expiring' => 0, 'due_today' => 0, 'expired' => 0];

        Tenant::query()
            ->where('plan', '!=', 'tunnel')
            ->where('status', '!=', 'inactive')
            ->orderBy('id')
            ->chunkById(100, function ($tenants) use ($now, $sendAt, $daysBefore, $trialGetsEarlyReminder, $expiredLookbackDays, $dry, &$sent) {
                foreach ($tenants as $tenant) {
                    if ($tenant->awaitingEmailVerification()) {
                        continue;
                    }
                    $end = $tenant->subscriptionEndsAt();
                    if ($end === null) {
                        continue;
                    }

                    $key = $end->toIso8601String();

                    if ($end->greaterThan($now)) {
                        $daysLeft = (int) round($now->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()));
                        if ($daysLeft === 0) {
                            // Hari H: tunggu jam kirim, kecuali jatuh tempo lebih awal dari jam kirim.
                            if ($now->lessThan($sendAt) && $end->greaterThan($sendAt)) {
                                continue;
                            }
                            $kind = 'due_today';
                        } elseif ($daysLeft <= $daysBefore
                            && (($tenant->plan ?? '') !== 'trial' || $trialGetsEarlyReminder)) {
                            if ($now->lessThan($sendAt)) {
                                continue;
                            }
                            $kind = 'expiring';
                        } else {
                            continue;
                        }
                        if ($tenant->metaGet("notifications.{$kind}_for") === $key) {
                            continue;
                        }
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

                    Notification::send($recipients, $kind === 'expired'
                        ? new SubscriptionExpiredNotification($tenant)
                        : new SubscriptionExpiringNotification($tenant, $kind === 'due_today'));

                    $tenant->metaSet("notifications.{$kind}_for", $key);
                    $tenant->save();
                    $sent[$kind]++;
                }
            });

        $this->info("Email terkirim: {$sent['expiring']} H-{$daysBefore}, {$sent['due_today']} hari H, {$sent['expired']} sudah berakhir.");
        if (! $dry && array_sum($sent) > 0) {
            Log::info('mikpay.subscription-emails', $sent);
        }

        return self::SUCCESS;
    }
}
