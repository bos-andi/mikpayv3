<?php

namespace App\Console\Commands;

use App\Services\FonnteBillingReminderService;
use Illuminate\Console\Command;

class MikpayFonnteRemindersCommand extends Command
{
    protected $signature = 'mikpay:fonnte-reminders {--dry-run : Hanya simulasi, tidak memanggil API Fonnte}';

    protected $description = 'Kirim pengingat tagihan WhatsApp (Fonnte) sesuai jadwal & data billing PPP';

    public function handle(FonnteBillingReminderService $service): int
    {
        $dry = (bool) $this->option('dry-run');
        if ($dry) {
            $this->warn('Mode dry-run: tidak ada pesan yang dikirim ke Fonnte.');
        }

        $t = $service->runAll($dry);

        $this->info(sprintf(
            'Selesai. Tenant diproses (dalam jam kirim): %d | terkirim/simulasi: %d | dilewati: %d | gagal: %d',
            $t['users'],
            $t['sent'],
            $t['skipped'],
            $t['failed']
        ));

        return self::SUCCESS;
    }
}
