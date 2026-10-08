<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('billing:auto-isolir')->dailyAt('01:15');
Schedule::command('billing:wa-reminders')->dailyAt('08:00');
// tunnel:sync-firewall butuh root (iptables) — dijalankan dari /etc/cron.d/mikpay-tunnel-firewall.
Schedule::command('tunnel:sync-subscription-access')->everyFiveMinutes();
Schedule::command('tunnel:kick-stale-sessions')->everyMinute()->withoutOverlapping();
Schedule::command('olt:poll')->everyTenMinutes();
Schedule::command('mikpay:subscription-emails')->hourly()->withoutOverlapping();
Schedule::command('voucher-shop:maintain')->everyMinute()->withoutOverlapping();
