<?php

namespace App\Jobs;

use App\Models\VoucherOrder;
use App\Services\VoucherShopService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FulfillVoucherOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 6;

    public int $timeout = 90;

    public function __construct(public int $orderId) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [15, 45, 120, 300, 600];
    }

    public function handle(VoucherShopService $shop): void
    {
        $order = VoucherOrder::query()->find($this->orderId);
        if (! $order || $order->status === VoucherOrder::STATUS_FULFILLED) {
            return;
        }

        $result = $shop->fulfill($order);
        if (! $result['ok']) {
            throw new \RuntimeException('Voucher '.$order->reference.': '.$result['message']);
        }
    }

    public function failed(?\Throwable $e): void
    {
        $order = VoucherOrder::query()->find($this->orderId);
        if ($order && $order->status === VoucherOrder::STATUS_PAID) {
            app(VoucherShopService::class)->markFailed($order, $order->last_error ?: ($e?->getMessage() ?? 'Gagal membuat voucher.'));
        }
    }
}
