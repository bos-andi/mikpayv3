<?php

namespace App\Services;

use App\Helpers\BillingArrearsHelper;
use App\Models\BillingCustomer;
use App\Models\MikpayRouter;
use App\Models\TelegramGraceOverride;
use App\Models\TelegramPppConnectionState;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramBotService
{
    private const CONNECTION_NOTIFY_COOLDOWN_MINUTES = 3;

    public function sendTestMessage(User $user): bool
    {
        $token = trim((string) ($user->telegram_bot_token ?? ''));
        $chatId = trim((string) ($user->telegram_chat_id ?? ''));
        if ($token === '' || $chatId === '') {
            return false;
        }

        $router = $this->resolveRouterSession($user) ?: '-';
        $text = "Test Telegram MIKPAY berhasil.\nUser: " . ($user->username ?? $user->name) . "\nRouter: {$router}\nWaktu: " . now()->format('Y-m-d H:i:s');

        return $this->sendMessage($token, $chatId, $text);
    }

    public function pollAllUsers(): void
    {
        $users = User::query()
            ->where('telegram_enabled', true)
            ->whereNotNull('telegram_bot_token')
            ->where('telegram_bot_token', '!=', '')
            ->whereNotNull('telegram_chat_id')
            ->where('telegram_chat_id', '!=', '')
            ->get();

        foreach ($users as $user) {
            $this->pollUser($user);
        }
    }

    public function monitorConnectionChangesAllUsers(): void
    {
        $users = User::query()
            ->where('telegram_enabled', true)
            ->where('telegram_notify_connection', true)
            ->whereNotNull('telegram_bot_token')
            ->where('telegram_bot_token', '!=', '')
            ->whereNotNull('telegram_chat_id')
            ->where('telegram_chat_id', '!=', '')
            ->get();

        foreach ($users as $user) {
            $this->monitorConnectionChangesUser($user);
        }
    }

    public function pollUser(User $user): void
    {
        $token = (string) $user->telegram_bot_token;
        $chatId = (string) $user->telegram_chat_id;

        $offset = $user->telegram_last_update_id ? ((int) $user->telegram_last_update_id + 1) : null;
        $payload = ['timeout' => 0, 'allowed_updates' => ['message']];
        if ($offset !== null) {
            $payload['offset'] = $offset;
        }

        $response = Http::timeout(15)->post($this->apiUrl($token, 'getUpdates'), $payload);
        if (! $response->ok()) {
            Log::warning('Telegram poll response not OK', [
                'user_id' => $user->id,
                'username' => $user->username ?? $user->name,
                'status' => $response->status(),
            ]);
            return;
        }
        $json = $response->json();
        if (! is_array($json) || ! ($json['ok'] ?? false)) {
            return;
        }

        $updates = $json['result'] ?? [];
        if (! is_array($updates)) {
            return;
        }

        $lastUpdateId = $user->telegram_last_update_id;
        foreach ($updates as $update) {
            $updateId = (int) ($update['update_id'] ?? 0);
            if ($updateId > 0) {
                $lastUpdateId = max((int) ($lastUpdateId ?? 0), $updateId);
            }
            $message = $update['message'] ?? null;
            if (! is_array($message)) {
                continue;
            }
            $incomingChatId = (string) ($message['chat']['id'] ?? '');
            if ($incomingChatId !== $chatId) {
                Log::info('Telegram update skipped: chat_id mismatch', [
                    'user_id' => $user->id,
                    'username' => $user->username ?? $user->name,
                    'incoming_chat_id' => $incomingChatId,
                ]);
                continue;
            }
            $text = trim((string) ($message['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            Log::info('Telegram command received', [
                'user_id' => $user->id,
                'username' => $user->username ?? $user->name,
                'chat_id' => $incomingChatId,
                'command_text' => $text,
            ]);
            $this->handleCommand($user, $token, $chatId, $text);
        }

        if ($lastUpdateId !== $user->telegram_last_update_id) {
            $user->telegram_last_update_id = $lastUpdateId;
            $user->save();
        }
    }

    private function handleCommand(User $user, string $token, string $chatId, string $text): void
    {
        $parts = preg_split('/\s+/', $text) ?: [];
        $commandRaw = strtolower((string) ($parts[0] ?? ''));
        $command = Str::before($commandRaw, '@');
        $args = array_values(array_slice($parts, 1));

        if ($command === '/start' || $command === '/help') {
            $this->sendMessage($token, $chatId, $this->helpMessage($user));
            return;
        }

        if ($command === '/telat') {
            $this->sendMessage($token, $chatId, $this->formatArrearsList($user));
            return;
        }

        if ($command === '/isolir') {
            $secret = trim((string) ($args[0] ?? ''));
            if ($secret === '') {
                $this->sendMessage($token, $chatId, 'Format: /isolir nama_secret');
                return;
            }
            $ok = $this->setSecretDisabled($user, $secret, true);
            $this->sendMessage($token, $chatId, $ok ? "Secret *{$secret}* berhasil diisolir." : "Gagal isolir secret *{$secret}*.");
            return;
        }

        if ($command === '/buka') {
            $secret = trim((string) ($args[0] ?? ''));
            if ($secret === '') {
                $this->sendMessage($token, $chatId, 'Format: /buka nama_secret');
                return;
            }
            $ok = $this->setSecretDisabled($user, $secret, false);
            $this->sendMessage($token, $chatId, $ok ? "Secret *{$secret}* berhasil dibuka." : "Gagal buka isolir secret *{$secret}*.");
            return;
        }

        if ($command === '/tunda') {
            $secret = trim((string) ($args[0] ?? ''));
            $date = trim((string) ($args[1] ?? ''));
            if ($secret === '' || $date === '') {
                $this->sendMessage($token, $chatId, 'Format: /tunda nama_secret YYYY-MM-DD');
                return;
            }
            try {
                $parsed = Carbon::createFromFormat('Y-m-d', $date);
            } catch (\Throwable $e) {
                $parsed = false;
            }
            if (! $parsed || $parsed->format('Y-m-d') !== $date) {
                $this->sendMessage($token, $chatId, 'Tanggal tidak valid. Gunakan format YYYY-MM-DD.');
                return;
            }
            $routerSession = $this->resolveRouterSession($user);
            TelegramGraceOverride::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'router_session' => $routerSession,
                    'customer_name' => $secret,
                ],
                [
                    'grace_until' => $date,
                    'note' => 'Diset via Telegram',
                ]
            );
            $this->sendMessage($token, $chatId, "Tunda isolir *{$secret}* sampai *{$date}*.");
            return;
        }

        $this->sendMessage($token, $chatId, 'Perintah tidak dikenal. Ketik /help');
    }

    private function formatArrearsList(User $user): string
    {
        $routerSession = $this->resolveRouterSession($user);

        $customers = BillingCustomer::query()
            ->orderBy('customer_name')
            ->when($routerSession, function ($q, $session) {
                $q->where(function ($q2) use ($session) {
                    $q2->where('router_session', $session)->orWhereNull('router_session');
                });
            })
            ->get();

        if ($customers->isEmpty()) {
            return 'Tidak ada data pelanggan tagihan PPP.';
        }

        $today = now()->toDateString();
        $graceMap = TelegramGraceOverride::query()
            ->where('user_id', $user->id)
            ->where(function ($q) use ($routerSession) {
                if ($routerSession) {
                    $q->where('router_session', $routerSession)->orWhereNull('router_session');
                } else {
                    $q->whereNull('router_session');
                }
            })
            ->get()
            ->keyBy(fn ($item) => strtolower((string) $item->customer_name));

        $rows = [];
        foreach ($customers as $customer) {
            $dueDay = (int) ($customer->due_day ?: 10);
            $arrears = BillingArrearsHelper::getUnpaidPeriodsSummary(
                (string) $customer->customer_name,
                $dueDay,
                (int) ($customer->monthly_fee ?? 0),
                $routerSession ? (string) $routerSession : null
            );
            $pastCount = (int) ($arrears['unpaid_past_count'] ?? 0);
            if ($pastCount <= 0) {
                continue;
            }

            $key = strtolower((string) $customer->customer_name);
            $grace = $graceMap->get($key);
            if ($grace && (string) $grace->grace_until >= $today) {
                continue;
            }

            $rows[] = [
                'name' => (string) $customer->customer_name,
                'past' => $pastCount,
                'label' => (string) ($arrears['label'] ?? ''),
            ];
        }

        if (empty($rows)) {
            return 'Tidak ada pelanggan telat (atau masih masa tunda).';
        }

        usort($rows, fn ($a, $b) => $b['past'] <=> $a['past']);
        $rows = array_slice($rows, 0, 20);

        $lines = ["Daftar pelanggan telat (" . count($rows) . "):"];
        foreach ($rows as $idx => $row) {
            $lines[] = ($idx + 1) . '. ' . $row['name'] . ' - ' . $row['label'];
        }
        $lines[] = '';
        $lines[] = 'Aksi cepat:';
        $lines[] = '/isolir nama_secret';
        $lines[] = '/buka nama_secret';
        $lines[] = '/tunda nama_secret YYYY-MM-DD';

        return implode("\n", $lines);
    }

    private function setSecretDisabled(User $user, string $secretName, bool $disabled): bool
    {
        $routerSession = $this->resolveRouterSession($user);
        if (! $routerSession) {
            Log::warning('Telegram isolate/open failed: router session not resolved', [
                'user_id' => $user->id,
                'username' => $user->username ?? $user->name,
                'secret' => $secretName,
                'disabled' => $disabled,
            ]);
            return false;
        }

        $router = MikpayRouter::query()
            ->where('user_id', $user->id)
            ->where('session_name', $routerSession)
            ->where('status', 'enable')
            ->first();
        if (! $router) {
            Log::warning('Telegram isolate/open failed: router not found for user', [
                'user_id' => $user->id,
                'username' => $user->username ?? $user->name,
                'router_session' => $routerSession,
                'secret' => $secretName,
                'disabled' => $disabled,
            ]);
            return false;
        }

        $mikrotik = new MikrotikService;
        if (! $mikrotik->connectRouter($router)) {
            $mikrotik->disconnect();
            Log::warning('Telegram isolate/open failed: cannot connect router', [
                'user_id' => $user->id,
                'username' => $user->username ?? $user->name,
                'router_session' => $routerSession,
                'secret' => $secretName,
                'disabled' => $disabled,
            ]);
            return false;
        }

        try {
            $secrets = $mikrotik->comm('/ppp/secret/print');
            if (! is_array($secrets)) {
                $mikrotik->disconnect();
                Log::warning('Telegram isolate/open failed: invalid secret list response', [
                    'user_id' => $user->id,
                    'username' => $user->username ?? $user->name,
                    'router_session' => $routerSession,
                    'secret' => $secretName,
                    'disabled' => $disabled,
                ]);
                return false;
            }
            $id = null;
            $normalizedInput = strtolower(trim($secretName));
            foreach ($secrets as $secret) {
                $currentName = strtolower(trim((string) ($secret['name'] ?? '')));
                if ($currentName === $normalizedInput) {
                    $id = $secret['.id'] ?? null;
                    break;
                }
            }
            if (! $id) {
                $mikrotik->disconnect();
                Log::warning('Telegram isolate/open failed: secret not found', [
                    'user_id' => $user->id,
                    'username' => $user->username ?? $user->name,
                    'router_session' => $routerSession,
                    'secret' => $secretName,
                    'disabled' => $disabled,
                ]);
                return false;
            }

            $mikrotik->comm('/ppp/secret/set', [
                '.id' => $id,
                'disabled' => $disabled ? 'yes' : 'no',
            ]);

            // If isolating, also terminate existing active sessions immediately.
            if ($disabled) {
                $activeList = $mikrotik->comm('/ppp/active/print');
                if (is_array($activeList)) {
                    foreach ($activeList as $active) {
                        $activeName = strtolower(trim((string) ($active['name'] ?? '')));
                        $activeId = (string) ($active['.id'] ?? '');
                        if ($activeName === $normalizedInput && $activeId !== '') {
                            $mikrotik->comm('/ppp/active/remove', ['.id' => $activeId]);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            $mikrotik->disconnect();
            Log::error('Telegram isolate/open failed: mikrotik exception', [
                'user_id' => $user->id,
                'username' => $user->username ?? $user->name,
                'router_session' => $routerSession,
                'secret' => $secretName,
                'disabled' => $disabled,
                'error' => $e->getMessage(),
            ]);
            return false;
        }

        $mikrotik->disconnect();
        Log::info('Telegram isolate/open success', [
            'user_id' => $user->id,
            'username' => $user->username ?? $user->name,
            'router_session' => $routerSession,
            'secret' => $secretName,
            'disabled' => $disabled,
        ]);
        return true;
    }

    private function monitorConnectionChangesUser(User $user): void
    {
        $routerSession = $this->resolveRouterSession($user);
        if (! $routerSession) {
            return;
        }

        $router = MikpayRouter::query()
            ->where('user_id', $user->id)
            ->where('session_name', $routerSession)
            ->where('status', 'enable')
            ->first();
        if (! $router) {
            return;
        }

        $mikrotik = new MikrotikService;
        if (! $mikrotik->connectRouter($router)) {
            $mikrotik->disconnect();
            return;
        }

        try {
            $activeList = $mikrotik->comm('/ppp/active/print');
            $activeList = is_array($activeList) ? $activeList : [];
            $secretList = $mikrotik->comm('/ppp/secret/print');
            $secretList = is_array($secretList) ? $secretList : [];
        } catch (\Throwable $e) {
            $mikrotik->disconnect();
            return;
        }
        $mikrotik->disconnect();

        $onlineMap = [];
        foreach ($activeList as $active) {
            $name = (string) ($active['name'] ?? '');
            if ($name !== '') {
                $onlineMap[$name] = true;
            }
        }

        $now = now();
        $token = (string) $user->telegram_bot_token;
        $chatId = (string) $user->telegram_chat_id;

        foreach ($secretList as $secret) {
            $name = trim((string) ($secret['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $isOnline = isset($onlineMap[$name]);
            $isDisabled = strtolower((string) ($secret['disabled'] ?? 'no')) === 'yes';

            $newState = $isOnline ? 'online' : ($isDisabled ? 'offline_admin' : 'offline_link');
            $stateRow = TelegramPppConnectionState::firstOrNew([
                'user_id' => $user->id,
                'router_session' => $routerSession,
                'secret_name' => $name,
            ]);

            if (! $stateRow->exists) {
                $stateRow->last_state = $newState;
                if ($isOnline) {
                    $stateRow->last_seen_online_at = $now;
                }
                $stateRow->save();
                continue;
            }

            $oldState = (string) $stateRow->last_state;
            if ($oldState === $newState) {
                if ($isOnline) {
                    $stateRow->last_seen_online_at = $now;
                    $stateRow->save();
                }
                continue;
            }

            $stateRow->last_state = $newState;
            if ($isOnline) {
                $stateRow->last_seen_online_at = $now;
            }

            $lastNotifiedAt = $stateRow->last_notified_at;
            $cooldownPassed = ! $lastNotifiedAt || $lastNotifiedAt->lte($now->copy()->subMinutes(self::CONNECTION_NOTIFY_COOLDOWN_MINUTES));
            if ($cooldownPassed) {
                $message = $this->buildConnectionTransitionMessage($name, $routerSession, $oldState, $newState);
                $sent = $this->sendMessage($token, $chatId, $message);
                if ($sent) {
                    $stateRow->last_notified_state = $newState;
                    $stateRow->last_notified_at = $now;
                }
            }

            $stateRow->save();
        }
    }

    private function buildConnectionTransitionMessage(string $secretName, string $routerSession, string $oldState, string $newState): string
    {
        $statusLabel = match ($newState) {
            'online' => 'ON (tersambung)',
            'offline_admin' => 'OFF (dimatikan admin/isolir)',
            default => 'OFF (link/kabel putus atau CPE mati)',
        };

        $reasonLabel = match ($newState) {
            'online' => 'ONLINE_RECOVERED',
            'offline_admin' => 'ADMIN_DISABLED',
            default => 'LINK_DOWN_OR_CLIENT_OFFLINE',
        };

        return "Notifikasi PPP\nRouter: {$routerSession}\nSecret: {$secretName}\nStatus: {$statusLabel}\nReason: {$reasonLabel}\nSebelumnya: {$oldState}\nWaktu: " . now()->format('Y-m-d H:i:s');
    }

    private function resolveRouterSession(User $user): ?string
    {
        $session = trim((string) ($user->telegram_router_session ?? ''));
        if ($session !== '') {
            return $session;
        }

        return MikpayRouter::query()
            ->where('user_id', $user->id)
            ->where('status', 'enable')
            ->orderBy('id')
            ->value('session_name');
    }

    private function helpMessage(User $user): string
    {
        $router = $this->resolveRouterSession($user) ?: '-';
        return "Telegram bot aktif.\nRouter: {$router}\n\nPerintah:\n/start\n/help\n/telat\n/isolir nama_secret\n/buka nama_secret\n/tunda nama_secret YYYY-MM-DD";
    }

    private function sendMessage(string $token, string $chatId, string $text): bool
    {
        try {
            $response = Http::timeout(15)->post($this->apiUrl($token, 'sendMessage'), [
                'chat_id' => $chatId,
                'text' => $text,
            ]);
        } catch (\Throwable $e) {
            return false;
        }

        $json = $response->json();
        return $response->ok() && is_array($json) && ($json['ok'] ?? false) === true;
    }

    private function apiUrl(string $token, string $method): string
    {
        return "https://api.telegram.org/bot{$token}/{$method}";
    }
}
