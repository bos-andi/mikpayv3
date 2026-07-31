<?php

namespace App\Console\Commands;

use App\Services\WaapiBillingReminderService;
use Illuminate\Console\Command;

class BillingWaRemindersCommand extends Command
{
    protected $signature = 'billing:wa-reminders {--dry-run : Simulasi tanpa kirim WA}';

    protected $description = 'Kirim pengingat tagihan WhatsApp (WaAPI) — H-3 / H-0 / tunggakan';

    public function handle(WaapiBillingReminderService $service): int
    {
        $dry = (bool) $this->option('dry-run');
        if ($dry) {
            $this->warn('Mode dry-run: tidak ada pesan yang dikirim.');
        }

        $this->info('Mulai billing:wa-reminders @ '.now()->toDateTimeString().' ('.config('app.timezone').')');

        $t = $service->runAll($dry);

        $summary = sprintf(
            'Selesai. Tenant: %d | terkirim/simulasi: %d | dilewati: %d | gagal: %d',
            $t['tenants'],
            $t['sent'],
            $t['skipped'],
            $t['failed']
        );
        $this->info($summary);

        if (($t['sent'] ?? 0) > 0 || ($t['failed'] ?? 0) > 0) {
            // LOG_LEVEL=error → pakai error agar jejak kirim/gagal tetap terlihat.
            \Illuminate\Support\Facades\Log::error('billing.wa-reminders.summary', $t + [
                'dry_run' => $dry,
                'at' => now()->toIso8601String(),
            ]);
        }

        if (($t['failed'] ?? 0) > 0) {
            $this->error('Ada pengiriman gagal — cek koneksi device WaAPI (sering: DISCONNECTED).');
        }

        return self::SUCCESS;
    }
}
