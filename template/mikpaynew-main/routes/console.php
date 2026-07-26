<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('mikpay:telegram-poll')->everyMinute()->withoutOverlapping();

// Pengingat tagihan WhatsApp (Fonnte): hanya memproses user yang jam sekarang masuk rentang di pengaturan Fonnte.
Schedule::command('mikpay:fonnte-reminders')->everyFifteenMinutes()->withoutOverlapping();
