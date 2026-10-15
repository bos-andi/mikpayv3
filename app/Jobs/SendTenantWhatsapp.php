<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\WaApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Kirim pesan WA lewat device WaAPI milik tenant (di-queue agar halaman publik tidak menunggu API).
 */
class SendTenantWhatsapp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public int $tenantId,
        public string $phone,
        public string $message,
    ) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(WaApiService $waapi): void
    {
        $tenant = Tenant::query()->find($this->tenantId);
        if (! $tenant || ! $tenant->waapiEnabled() || ! $tenant->hasWaapiToken()) {
            return;
        }

        $result = $waapi->sendText($tenant->plainWaapiToken(), $this->phone, $this->message);
        if (! $result['ok'] && ($result['status'] === null || $result['status'] >= 500)) {
            throw new \RuntimeException('WaAPI: '.$result['message']);
        }
    }
}
