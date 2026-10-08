<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'subdomain',
        'status',
        'plan',
        'vpn_status',
        'remote_host',
        'router_label',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function routers(): HasMany
    {
        return $this->hasMany(Router::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function tunnelAccounts(): HasMany
    {
        return $this->hasMany(TunnelAccount::class);
    }

    public function platformPayments(): HasMany
    {
        return $this->hasMany(PlatformPendingPayment::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'referred_by_partner_id');
    }

    public function subscriptionEndsAt(): ?Carbon
    {
        $raw = $this->metaGet('subscription_ends_at') ?: $this->metaGet('trial_ends_at');
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        try {
            // Selalu hitung & bandingkan dalam WIB (Asia/Jakarta).
            return Carbon::parse($raw)->timezone(app_timezone());
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Pemilik baru daftar dan belum klik link verifikasi email: panel & VPN dikunci.
     */
    public function awaitingEmailVerification(): bool
    {
        return (bool) $this->metaGet('email_verification_pending', false);
    }

    public function subscriptionIsActive(): bool
    {
        // Nonaktif = panel terkunci, harus bayar/perpanjang sendiri.
        if ($this->status === 'inactive') {
            return false;
        }

        $end = $this->subscriptionEndsAt();
        if ($end === null) {
            // Legacy tanpa tanggal berakhir — tetap diizinkan.
            return $this->status === 'active' || $this->status === 'trial';
        }

        // Habis tepat pada tanggal & jam WIB (bukan sampai akhir hari kalender).
        return $end->greaterThan(wib_now());
    }

    public function subscriptionExpired(): bool
    {
        $end = $this->subscriptionEndsAt();
        if ($end === null) {
            return false;
        }

        return ! $end->greaterThan(wib_now());
    }

    /**
     * Panel terkunci dan harus diarahkan ke halaman pembayaran perpanjang.
     */
    public function needsRenewal(): bool
    {
        return ($this->plan ?? '') !== 'tunnel' && ! $this->subscriptionIsActive();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'active' => 'Aktif',
            'inactive' => 'Nonaktif',
            'trial' => 'Trial',
            default => ucfirst((string) $this->status),
        };
    }

    /**
     * Perpanjang masa aktif langganan platform (dari approve pembayaran).
     */
    public function extendPlatformSubscription(string $package, int $durationDays): void
    {
        $now = wib_now();
        $currentEnd = $this->subscriptionEndsAt();
        $base = ($currentEnd && $currentEnd->greaterThan($now)) ? $currentEnd : $now;
        $newEnd = $base->copy()->timezone(app_timezone())->addDays(max(1, $durationDays));

        $this->plan = $package;
        $this->status = 'active';
        $this->metaSet('subscription_package', $package);
        $this->metaSet('subscription_status', 'active');
        $this->metaSet('subscription_starts_at', $now->toIso8601String());
        $this->metaSet('subscription_ends_at', $newEnd->toIso8601String());
        $this->save();

        if (($this->plan ?? '') !== 'tunnel') {
            app(\App\Services\TunnelService::class)->syncAccessForTenant($this);
        }
    }

    public function platformBalance(): int
    {
        return max(0, (int) $this->metaGet('platform_balance', 0));
    }

    public function addPlatformBalance(int $amount, string $note = ''): void
    {
        if ($amount < 1) {
            return;
        }

        $this->metaSet('platform_balance', $this->platformBalance() + $amount);
        $this->appendBalanceLedger('credit', $amount, $note);
        $this->save();
    }

    /**
     * Saldo hasil inject superadmin (bukan uang asli) — tidak menghasilkan komisi affiliate.
     */
    public function bonusPlatformBalance(): int
    {
        return min($this->platformBalance(), max(0, (int) $this->metaGet('platform_balance_bonus', 0)));
    }

    public function addBonusPlatformBalance(int $amount): void
    {
        $this->metaSet('platform_balance_bonus', $this->bonusPlatformBalance() + max(0, $amount));
        $this->save();
    }

    /**
     * Kurangi porsi saldo bonus untuk pembayaran dari saldo. Panggil sebelum deductPlatformBalance().
     *
     * @return int Nominal saldo bonus yang terpakai.
     */
    public function consumeBonusPlatformBalance(int $amount): int
    {
        $bonus = $this->bonusPlatformBalance();
        $used = min($bonus, max(0, $amount));
        $this->metaSet('platform_balance_bonus', $bonus - $used);

        return $used;
    }

    public function deductPlatformBalance(int $amount): bool
    {
        if ($amount < 1 || $this->platformBalance() < $amount) {
            return false;
        }

        $this->metaSet('platform_balance', $this->platformBalance() - $amount);
        $this->appendBalanceLedger('debit', $amount);
        $this->save();

        return true;
    }

    protected function appendBalanceLedger(string $type, int $amount, string $note = ''): void
    {
        $ledger = is_array($this->metaGet('platform_balance_ledger')) ? $this->metaGet('platform_balance_ledger') : [];
        $ledger[] = [
            'type' => $type,
            'amount' => $amount,
            'note' => $note,
            'at' => wib_now()->toIso8601String(),
        ];
        $this->metaSet('platform_balance_ledger', array_slice($ledger, -50));
    }

    public function panelHost(): string
    {
        return $this->subdomain.'.'.config('mikpay.tenant_base_domain');
    }

    public function remoteHost(): string
    {
        return $this->remote_host
            ?: $this->subdomain.'.'.config('mikpay.remote_base_domain');
    }

    public function metaGet(string $key, mixed $default = null): mixed
    {
        $meta = is_array($this->meta) ? $this->meta : [];

        return data_get($meta, $key, $default);
    }

    public function metaSet(string $key, mixed $value): void
    {
        $meta = is_array($this->meta) ? $this->meta : [];
        data_set($meta, $key, $value);
        $this->meta = $meta;
    }

    public function xenditEnabled(): bool
    {
        // Legacy: dianggap siap jika gateway Xendit resolved siap.
        return \App\Support\PaymentGateway::xenditReady($this);
    }

    public function paymentMode(): string
    {
        return \App\Support\PaymentGateway::modeFor($this);
    }

    public function paymentActive(): string
    {
        return \App\Support\PaymentGateway::active($this);
    }

    public function ownXenditSecretKey(): string
    {
        return $this->decryptMetaSecret('xendit.secret_key');
    }

    public function ownXenditCallbackToken(): string
    {
        return $this->decryptMetaSecret('xendit.callback_token');
    }

    public function hasMetaSecret(string $key): bool
    {
        return $this->decryptMetaSecret($key) !== '';
    }

    public function metaPlain(string $key, mixed $default = null): mixed
    {
        return $this->metaGet($key, $default);
    }

    /** @deprecated Gunakan PaymentGateway::resolveXenditSecretKey */
    public function plainXenditSecretKey(): string
    {
        return \App\Support\PaymentGateway::resolveXenditSecretKey($this);
    }

    /** @deprecated Gunakan PaymentGateway::resolveXenditCallbackToken */
    public function plainXenditCallbackToken(): string
    {
        return \App\Support\PaymentGateway::resolveXenditCallbackToken($this);
    }

    public function hasXenditSecretKey(): bool
    {
        return \App\Support\PaymentGateway::resolveXenditSecretKey($this) !== '';
    }

    public function hasXenditCallbackToken(): bool
    {
        return \App\Support\PaymentGateway::resolveXenditCallbackToken($this) !== '';
    }

    public function hasOwnXenditSecretKey(): bool
    {
        return $this->ownXenditSecretKey() !== '';
    }

    public function hasOwnXenditCallbackToken(): bool
    {
        return $this->ownXenditCallbackToken() !== '';
    }

    public function waapiEnabled(): bool
    {
        return (bool) $this->metaGet('waapi.enabled', $this->metaGet('fonnte.enabled', false));
    }

    /**
     * Masa tenggang isolir (hari setelah jatuh tempo). Override di Settings → Billing.
     */
    public function isolirGraceDays(): int
    {
        $raw = $this->metaGet('billing.grace_days');
        if ($raw === null || $raw === '') {
            return max(0, min(60, (int) config('mikpay.billing.grace_days', 2)));
        }

        return max(0, min(60, (int) $raw));
    }

    /** @deprecated Gunakan waapiEnabled() */
    public function fonnteEnabled(): bool
    {
        return $this->waapiEnabled();
    }

    public function plainWaapiToken(): string
    {
        $token = $this->decryptMetaSecret('waapi.api_token');
        if ($token !== '') {
            return $token;
        }

        // Legacy Fonnte key (migrasi soft)
        return $this->decryptMetaSecret('fonnte.api_token');
    }

    /** @deprecated */
    public function plainFonnteToken(): string
    {
        return $this->plainWaapiToken();
    }

    public function hasWaapiToken(): bool
    {
        return $this->plainWaapiToken() !== '';
    }

    /**
     * Status koneksi device WaAPI (di-cache singkat agar tidak hit API tiap request).
     *
     * @return array{state: string, label: string, connected: bool, phone: ?string, detail: ?string}
     */
    public function waapiGatewayStatus(bool $forceRefresh = false): array
    {
        if (! $this->waapiEnabled() || ! $this->hasWaapiToken()) {
            return [
                'state' => 'off',
                'label' => 'WA Gateway Off',
                'connected' => false,
                'phone' => null,
                'detail' => null,
            ];
        }

        $cacheKey = 'waapi.device_status.'.$this->id;
        if ($forceRefresh) {
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
        }

        $cached = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addMinutes(3), function () {
            try {
                $result = app(\App\Services\WaApiService::class)->deviceStatus($this->plainWaapiToken());
                $device = is_array($result['device'] ?? null) ? $result['device'] : [];

                return [
                    'ok' => (bool) ($result['ok'] ?? false),
                    'connected' => (bool) ($result['connected'] ?? false),
                    'phone' => isset($device['phone']) ? (string) $device['phone'] : null,
                    'detail' => (string) ($result['message'] ?? ''),
                    'status_label' => isset($device['status_label']) ? (string) $device['status_label'] : null,
                ];
            } catch (\Throwable $e) {
                return [
                    'ok' => false,
                    'connected' => false,
                    'phone' => null,
                    'detail' => $e->getMessage(),
                    'status_label' => null,
                ];
            }
        });

        if (! empty($cached['connected'])) {
            return [
                'state' => 'connected',
                'label' => 'WA Gateway Connected',
                'connected' => true,
                'phone' => $cached['phone'] ?? null,
                'detail' => $cached['detail'] ?? null,
            ];
        }

        return [
            'state' => 'disconnected',
            'label' => 'WA Device Offline',
            'connected' => false,
            'phone' => $cached['phone'] ?? null,
            'detail' => $cached['detail'] ?? 'Perangkat WhatsApp belum terhubung di wa-api.id',
        ];
    }

    /** @deprecated */
    public function hasFonnteToken(): bool
    {
        return $this->hasWaapiToken();
    }

    public function waapiReminderTemplate(): ?string
    {
        $t = $this->metaGet('waapi.reminder_template', $this->metaGet('fonnte.reminder_template'));

        return is_string($t) && trim($t) !== '' ? $t : null;
    }

    /** @deprecated */
    public function fonnteReminderTemplate(): ?string
    {
        return $this->waapiReminderTemplate();
    }

    public function setEncryptedMetaSecret(string $key, ?string $plain): void
    {
        if ($plain === null || $plain === '') {
            return;
        }

        $this->metaSet($key, Crypt::encryptString($plain));
    }

    public function clearMetaSecret(string $key): void
    {
        $meta = is_array($this->meta) ? $this->meta : [];
        data_forget($meta, $key);
        $this->meta = $meta;
    }

    protected function decryptMetaSecret(string $key): string
    {
        $raw = $this->metaGet($key);
        if (! is_string($raw) || $raw === '') {
            return '';
        }

        try {
            return Crypt::decryptString($raw);
        } catch (\Throwable) {
            // Legacy plain storage fallback
            return $raw;
        }
    }
}
