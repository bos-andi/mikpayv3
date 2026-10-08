<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Klien kecil untuk management interface OpenVPN server MikPay (127.0.0.1, berpassword).
 * Dipakai untuk melihat client yang sedang tersambung dan memutus sesi akun yang sudah dihapus/nonaktif.
 */
class OpenVpnManagementService
{
    public function isConfigured(): bool
    {
        return (string) config('mikpay.tunnel.management.password', '') !== '';
    }

    /**
     * @return list<array{common_name: string, real_address: string, virtual_address: string}>|null null jika management tidak bisa dihubungi
     */
    public function connectedClients(): ?array
    {
        $lines = $this->command('status 2');
        if ($lines === null) {
            return null;
        }

        $clients = [];
        foreach ($lines as $line) {
            if (! str_starts_with($line, 'CLIENT_LIST,')) {
                continue;
            }
            $cols = str_getcsv($line);
            $clients[] = [
                'common_name' => (string) ($cols[1] ?? ''),
                'real_address' => (string) ($cols[2] ?? ''),
                'virtual_address' => (string) ($cols[3] ?? ''),
            ];
        }

        return $clients;
    }

    /**
     * Putus semua sesi dengan common name (= username VPN) ini.
     */
    public function kill(string $commonName): bool
    {
        $commonName = trim($commonName);
        if ($commonName === '' || preg_match('/[\s"\\\\]/', $commonName)) {
            return false;
        }

        $lines = $this->command('kill '.$commonName);
        if ($lines === null) {
            return false;
        }

        $killed = collect($lines)->contains(fn (string $l) => str_starts_with($l, 'SUCCESS:'));
        if ($killed) {
            Log::info('tunnel.ovpn.killed', ['username' => $commonName]);
        }

        return $killed;
    }

    /**
     * @return list<string>|null
     */
    protected function command(string $command): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $host = (string) config('mikpay.tunnel.management.host', '127.0.0.1');
        $port = (int) config('mikpay.tunnel.management.port', 7505);
        $timeout = 3.0;

        $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if (! $socket) {
            Log::warning('tunnel.ovpn.management_unreachable', ['error' => $errstr ?: $errno]);

            return null;
        }

        stream_set_timeout($socket, (int) $timeout);

        try {
            $this->readUntil($socket, fn (string $l) => str_starts_with($l, 'ENTER PASSWORD:'), true);
            fwrite($socket, config('mikpay.tunnel.management.password')."\n");
            $auth = $this->readUntil($socket, fn (string $l) => str_starts_with($l, 'SUCCESS: password') || str_starts_with($l, 'ERROR'));
            if (! collect($auth)->contains(fn (string $l) => str_starts_with($l, 'SUCCESS: password'))) {
                Log::warning('tunnel.ovpn.management_auth_failed');

                return null;
            }

            fwrite($socket, $command."\n");
            $isStatus = str_starts_with($command, 'status');
            $out = $this->readUntil($socket, fn (string $l) => $isStatus
                ? $l === 'END'
                : (str_starts_with($l, 'SUCCESS:') || str_starts_with($l, 'ERROR:')));

            fwrite($socket, "quit\n");

            return $out;
        } finally {
            fclose($socket);
        }
    }

    /**
     * @param  resource  $socket
     * @return list<string>
     */
    protected function readUntil($socket, callable $done, bool $partialPrompt = false): array
    {
        $lines = [];
        $deadline = microtime(true) + 3;

        while (microtime(true) < $deadline && ! feof($socket)) {
            if ($partialPrompt) {
                $chunk = fread($socket, 256);
                if ($chunk === false || $chunk === '') {
                    continue;
                }
                foreach (preg_split('/\r?\n/', $chunk) as $l) {
                    $l = trim($l);
                    if ($l === '') {
                        continue;
                    }
                    $lines[] = $l;
                    if ($done($l)) {
                        return $lines;
                    }
                }

                continue;
            }

            $line = fgets($socket);
            if ($line === false) {
                continue;
            }
            $line = rtrim($line, "\r\n");
            if (str_starts_with($line, '>INFO') || $line === '') {
                continue;
            }
            $lines[] = $line;
            if ($done($line)) {
                return $lines;
            }
        }

        return $lines;
    }
}
