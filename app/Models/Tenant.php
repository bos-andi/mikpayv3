<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
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

    public function subscriptionIsActive(): bool
    {
        if ($this->status === 'suspended' || $this->status === 'inactive') {
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
