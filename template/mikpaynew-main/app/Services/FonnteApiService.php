<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteApiService
{
    private const SEND_URL = 'https://api.fonnte.com/send';

    /**
     * @return array{ok: bool, status: int|null, body: string}
     */
    public function sendText(string $apiToken, string $target, string $message): array
    {
        $token = trim($apiToken);
        if ($token === '' || trim($target) === '') {
            return ['ok' => false, 'status' => null, 'body' => 'missing token or target'];
        }

        try {
            $response = Http::timeout(45)
                ->withHeaders(['Authorization' => $token])
                ->asForm()
                ->post(self::SEND_URL, [
                    'target' => $target,
                    'message' => $message,
                ]);
        } catch (\Throwable $e) {
            Log::warning('fonnte.send.http_error', ['message' => $e->getMessage()]);

            return ['ok' => false, 'status' => null, 'body' => $e->getMessage()];
        }

        $ok = $response->successful();
        if (! $ok) {
            Log::warning('fonnte.send.failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return [
            'ok' => $ok,
            'status' => $response->status(),
            'body' => $response->body(),
        ];
    }
}
