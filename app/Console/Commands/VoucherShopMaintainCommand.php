<?php

namespace App\Console\Commands;

use App\Services\VoucherShopService;
use Illuminate\Console\Command;

class VoucherShopMaintainCommand extends Command
{
    protected $signature = 'voucher-shop:maintain';

    protected $description = 'Kedaluwarsakan pesanan voucher belum dibayar dan antrekan ulang voucher lunas yang macet';

    public function handle(VoucherShopService $shop): int
    {
        $result = $shop->maintain();
        if ($result['expired'] || $result['requeued']) {
            $this->info("Expired: {$result['expired']}, requeued: {$result['requeued']}");
        }

        return self::SUCCESS;
    }
}
