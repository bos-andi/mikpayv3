<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client WhatsApp via WaAPI (https://wa-api.id/docs)
 * Auth: Authorization: Bearer waapi_dev_... atau waapi_...
 * Send: POST /api/v1/send  → HTTP 202 queued
 */
class WaApiService
{
    /**
     * @return array{ok: bool, status: int|null, body: string, message: string, id?: string}
     */
    public function sendText(string $apiToken, string $target, string $message): array
    {
        $token = trim($apiToken);
        $target = $this->normalizeTarget($target);
        if ($token === '' || $target === '') {
            return ['ok' => false, 'status' => null, 'body' => '', 'message' => 'Token WaAPI atau nomor tujuan kosong.'];
        }

        $base = rtrim((string) config('mikpay.whatsapp.waapi_base_url', 'https://wa-api.id/api/v1'), '/');

        try {
            $response = Http::timeout(45)
                ->withToken($token)
                ->acceptJson()
                ->asJson()
                ->post($base.'/send', [
                    'target' => $target,
                    'message' => $message,
                ]);
        } catch (\Throwable $e) {
            Log::warning('waapi.send.http_error', ['message' => $e->getMessage()]);

            return ['ok' => false, 'status' => null, 'body' => $e->getMessage(), 'message' => $e->getMessage()];
        }

        $json = $response->json() ?? [];
        $body = $response->body();
        // 202 = queued (sukses diterima), 200 = ok
        $ok = $response->successful() || $response->status() === 202;

        if (! $ok) {
            Log::warning('waapi.send.failed', [
                'status' => $response->status(),
                'body' => $json ?: $body,
            ]);
        }

        $msg = (string) (
            $json['detail']
            ?? $json['reason']
            ?? ($ok ? 'Pesan diantrikan (WaAPI).' : 'Gagal kirim WA')
        );

        return [
            'ok' => $ok,
            'status' => $response->status(),
            'body' => $body,
            'message' => $msg,
            'id' => isset($json['id']) ? (string) $json['id'] : null,
        ];
    }

    /**
     * @return array{ok: bool, message: string, connected?: bool, device?: array}
     */
    public function deviceStatus(string $apiToken): array
    {
        $token = trim($apiToken);
        if ($token === '') {
            return ['ok' => false, 'message' => 'Token kosong.'];
        }

        $base = rtrim((string) config('mikpay.whatsapp.waapi_base_url', 'https://wa-api.id/api/v1'), '/');

        try {
            $response = Http::timeout(20)
                ->withToken($token)
                ->acceptJson()
                ->get($base.'/device');
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        $json = $response->json() ?? [];
        if (! $response->successful()) {
            return [
                'ok' => false,
                'message' => (string) ($json['reason'] ?? $json['detail'] ?? 'Gagal cek device'),
            ];
        }

        $device = is_array($json['device'] ?? null) ? $json['device'] : [];

        return [
            'ok' => true,
            'message' => (string) ($json['detail'] ?? 'ok'),
            'connected' => (bool) ($device['connected'] ?? false),
            'device' => $device,
        ];
    }

    public function normalizeTarget(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }
        if (! str_starts_with($digits, '62')) {
            $digits = '62'.$digits;
        }

        return $digits;
    }
}
