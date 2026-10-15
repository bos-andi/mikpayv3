<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\PlatformWalletService;
use Illuminate\Console\Command;

class SubscriptionAutoRenewCommand extends Command
{
    protected $signature = 'mikpay:auto-renew {--dry-run : Tampilkan tenant yang akan diperpanjang tanpa memotong saldo}';

    protected $description = 'Perpanjang langganan MikPay dari saldo untuk tenant yang mengaktifkan perpanjang otomatis';

    public function handle(PlatformWalletService $wallet): int
    {
        $dry = (bool) $this->option('dry-run');
        $counts = ['ok' => 0, 'failed' => 0];

        Tenant::query()
            ->where('plan', '!=', 'tunnel')
            ->where('status', '!=', 'inactive')
            ->orderBy('id')
            ->chunkById(100, function ($tenants) use ($wallet, $dry, &$counts) {
                foreach ($tenants as $tenant) {
                    if (! $tenant->metaGet('auto_renew.enabled', false)) {
                        continue;
                    }

                    $result = $wallet->attemptAutoRenew($tenant, $dry);
                    if ($result === null) {
                        continue;
                    }

                    $counts[$result['ok'] ? 'ok' : 'failed']++;
                    $this->line(sprintf('[%s] %s: %s', $result['ok'] ? 'ok' : 'gagal', $tenant->subdomain, $result['message']));
                }
            });

        $this->info("Perpanjang otomatis: {$counts['ok']} berhasil, {$counts['failed']} gagal.".($dry ? ' (dry-run)' : ''));

        return self::SUCCESS;
    }
}
