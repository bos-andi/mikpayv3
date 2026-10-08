<?php

namespace App\Services;

use App\Models\Partner;
use App\Models\PartnerCommission;
use App\Models\PartnerWithdrawal;
use App\Models\PlatformPendingPayment;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Support\PaymentGateway;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Program Affiliate: kode referral, komisi langganan, dan pencairan komisi affiliate.
 */
class PartnerProgramService
{
    public const COOKIE = 'mikpay_ref';

    public const DEFAULT_SETTINGS = [
        'enabled' => true,
        'first_percent' => 30,
        'recurring_percent' => 10,
        'recurring_months' => 12,
        'hold_days' => 7,
        'min_withdrawal' => 50000,
        'cookie_days' => 30,
        'payout_day' => 10,
        'referral_discount_percent' => 10,
    ];

    private const XENDIT_API = 'https://api.xendit.co';

    /**
     * @return array{enabled: bool, first_percent: float, recurring_percent: float, recurring_months: int, hold_days: int, min_withdrawal: int, cookie_days: int, payout_day: int, referral_discount_percent: float}
     */
    public function settings(): array
    {
        $raw = PlatformSetting::get()->partner_settings;
        $s = array_merge(self::DEFAULT_SETTINGS, is_array($raw) ? $raw : []);

        return [
            'enabled' => (bool) $s['enabled'],
            'first_percent' => max(0, min(100, round((float) $s['first_percent'], 2))),
            'recurring_percent' => max(0, min(100, round((float) $s['recurring_percent'], 2))),
            'recurring_months' => max(0, min(120, (int) $s['recurring_months'])),
            'hold_days' => max(0, min(60, (int) $s['hold_days'])),
            'min_withdrawal' => max(10000, (int) $s['min_withdrawal']),
            'cookie_days' => max(1, min(365, (int) $s['cookie_days'])),
            'payout_day' => max(0, min(28, (int) $s['payout_day'])),
            'referral_discount_percent' => max(0, min(90, round((float) $s['referral_discount_percent'], 2))),
        ];
    }

    /**
     * Diskon pembayaran pertama untuk pelanggan yang daftar dengan kode referral (0 = tidak ada).
     */
    public function referralDiscountPercent(): float
    {
        $s = $this->settings();

        return $s['enabled'] ? $s['referral_discount_percent'] : 0.0;
    }

    /**
     * Persen diskon yang berlaku untuk pembayaran paket berikutnya milik tenant ini.
     */
    public function firstPaymentDiscountFor(?Tenant $tenant): float
    {
        if (! $tenant?->referred_by_partner_id) {
            return 0.0;
        }

        $percent = $this->referralDiscountPercent();
        if ($percent <= 0) {
            return 0.0;
        }

        $hasPaid = PlatformPendingPayment::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'approved')
            ->where(fn ($q) => $q->whereNull('purpose')->orWhere('purpose', PlatformPendingPayment::PURPOSE_SUBSCRIPTION))
            ->exists();

        return $hasPaid ? 0.0 : $percent;
    }

    public static function applyDiscount(int $price, float $percent): int
    {
        return max(0, $price - (int) floor($price * $percent / 100));
    }

    /**
     * Tanggal pencairan berikutnya (null = diproses kapan saja oleh admin).
     */
    public function nextPayoutDate(): ?Carbon
    {
        $day = $this->settings()['payout_day'];
        if ($day < 1) {
            return null;
        }

        $today = wib_now()->locale('id')->startOfDay();
        $date = $today->copy()->day($day);

        return $date->lessThan($today) ? $date->addMonthNoOverflow()->day($day) : $date;
    }

    public function saveSettings(array $data): void
    {
        $setting = PlatformSetting::get();
        $setting->partner_settings = [
            'enabled' => (bool) ($data['enabled'] ?? false),
            'first_percent' => (float) $data['first_percent'],
            'recurring_percent' => (float) $data['recurring_percent'],
            'recurring_months' => (int) $data['recurring_months'],
            'hold_days' => (int) $data['hold_days'],
            'min_withdrawal' => (int) $data['min_withdrawal'],
            'cookie_days' => (int) $data['cookie_days'],
            'payout_day' => (int) ($data['payout_day'] ?? self::DEFAULT_SETTINGS['payout_day']),
            'referral_discount_percent' => (float) ($data['referral_discount_percent'] ?? self::DEFAULT_SETTINGS['referral_discount_percent']),
        ];
        $setting->save();
    }

    /**
     * @return array{first: float, recurring: float}
     */
    public function ratesFor(Partner $partner): array
    {
        $s = $this->settings();

        return [
            'first' => $partner->first_percent ?? $s['first_percent'],
            'recurring' => $partner->recurring_percent ?? $s['recurring_percent'],
        ];
    }

    public static function normalizeCode(?string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $code) ?? '');
    }

    public function findActiveByCode(?string $code): ?Partner
    {
        $code = self::normalizeCode($code);
        if ($code === '' || strlen($code) > 20) {
            return null;
        }

        return Partner::query()
            ->where('code', $code)
            ->where('status', Partner::STATUS_ACTIVE)
            ->first();
    }

    public function generateCode(string $name): string
    {
        $firstWord = self::normalizeCode(Str::ascii(strtok(trim($name), ' ') ?: ''));
        $base = substr(strlen($firstWord) >= 3 ? $firstWord : self::normalizeCode(Str::ascii($name)), 0, 8);
        if (strlen($base) < 3) {
            $base = 'AFF';
        }

        do {
            $code = $base.random_int(10, 99);
        } while (Partner::query()->where('code', $code)->exists());

        return $code;
    }

    /**
     * Kaitkan tenant baru ke affiliate perujuk. Email/WA affiliate sendiri tidak dihitung.
     */
    public function attachReferral(Tenant $tenant, ?string $code, string $registrantEmail, ?string $registrantPhone = null): ?Partner
    {
        $partner = $this->findActiveByCode($code);
        if (! $partner || ! $this->settings()['enabled']) {
            return null;
        }

        $partner->loadMissing('user');
        if (strcasecmp((string) $partner->user?->email, $registrantEmail) === 0) {
            return null;
        }

        $digits = static fn (?string $p) => ltrim(preg_replace('/\D/', '', (string) $p) ?? '', '0');
        if ($registrantPhone && $partner->phone && $digits($registrantPhone) !== '' && substr($digits($registrantPhone), -9) === substr($digits($partner->phone), -9)) {
            return null;
        }

        $tenant->forceFill(['referred_by_partner_id' => $partner->id])->save();

        Log::info('partner.referral.attached', ['partner_id' => $partner->id, 'tenant_id' => $tenant->id]);

        return $partner;
    }

    /**
     * Catat komisi untuk pembayaran langganan yang sudah approved. Tidak pernah melempar exception
     * supaya alur aktivasi langganan tidak terganggu.
     *
     * @param  int|null  $commissionableAmount  Bagian pembayaran yang berasal dari uang asli (bukan saldo bonus).
     */
    public function recordForPayment(PlatformPendingPayment $payment, ?int $commissionableAmount = null): ?PartnerCommission
    {
        try {
            if ($payment->status !== 'approved' || $payment->isDeposit()) {
                return null;
            }

            $tenant = $payment->tenant;
            if (! $tenant?->referred_by_partner_id) {
                return null;
            }

            $settings = $this->settings();
            $partner = Partner::query()->find($tenant->referred_by_partner_id);
            if (! $settings['enabled'] || ! $partner?->isActive()) {
                return null;
            }

            if (PartnerCommission::query()->where('platform_payment_id', $payment->id)->exists()) {
                return null;
            }

            $base = max(0, min((int) $payment->amount, $commissionableAmount ?? (int) $payment->amount));
            if ($base < 1) {
                return null;
            }

            $previous = PlatformPendingPayment::query()
                ->where('tenant_id', $tenant->id)
                ->where('status', 'approved')
                ->where(fn ($q) => $q->whereNull('purpose')->orWhere('purpose', PlatformPendingPayment::PURPOSE_SUBSCRIPTION))
                ->where('id', '<', $payment->id)
                ->orderBy('id')
                ->first();

            $rates = $this->ratesFor($partner);
            if (! $previous) {
                $kind = PartnerCommission::KIND_FIRST;
                $percent = $rates['first'];
            } else {
                $months = $settings['recurring_months'];
                $firstPaidAt = $previous->approved_at ?? $previous->created_at;
                if ($months < 1 || $firstPaidAt->copy()->addMonths($months)->isPast()) {
                    return null;
                }
                $kind = PartnerCommission::KIND_RECURRING;
                $percent = $rates['recurring'];
            }

            $amount = (int) floor($base * $percent / 100);
            if ($amount < 1) {
                return null;
            }

            $commission = PartnerCommission::query()->create([
                'partner_id' => $partner->id,
                'tenant_id' => $tenant->id,
                'tenant_name' => (string) $tenant->name,
                'platform_payment_id' => $payment->id,
                'package' => $payment->package,
                'kind' => $kind,
                'base_amount' => $base,
                'percent' => $percent,
                'amount' => $amount,
                'status' => PartnerCommission::STATUS_EARNED,
                'available_at' => now()->addDays($settings['hold_days']),
            ]);

            Log::info('partner.commission.recorded', [
                'partner_id' => $partner->id,
                'tenant_id' => $tenant->id,
                'payment_id' => $payment->payment_id,
                'amount' => $amount,
                'kind' => $kind,
            ]);

            return $commission;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    public function cancelCommission(PartnerCommission $commission, string $reason): array
    {
        if ($commission->isCancelled()) {
            return ['ok' => false, 'message' => 'Komisi ini sudah dibatalkan.'];
        }

        return DB::transaction(function () use ($commission, $reason) {
            Partner::query()->whereKey($commission->partner_id)->lockForUpdate()->first();

            $balance = $this->balance($commission->partner);
            if ($commission->isAvailable() && $balance['available'] < $commission->amount) {
                return ['ok' => false, 'message' => 'Komisi sudah ditarik affiliate sehingga tidak bisa dibatalkan.'];
            }

            $commission->forceFill([
                'status' => PartnerCommission::STATUS_CANCELLED,
                'cancel_reason' => $reason,
            ])->save();

            return ['ok' => true, 'message' => 'Komisi Rp '.number_format($commission->amount, 0, ',', '.').' dibatalkan.'];
        });
    }

    /**
     * @return array{total: int, on_hold: int, withdrawn: int, in_progress: int, available: int}
     */
    public function balance(Partner $partner): array
    {
        $earned = PartnerCommission::query()
            ->where('partner_id', $partner->id)
            ->where('status', PartnerCommission::STATUS_EARNED);

        $total = (int) (clone $earned)->sum('amount');
        $onHold = (int) (clone $earned)->where('available_at', '>', now())->sum('amount');

        $withdrawn = (int) PartnerWithdrawal::query()
            ->where('partner_id', $partner->id)
            ->where('status', PartnerWithdrawal::STATUS_COMPLETED)
            ->sum('amount');

        $inProgress = (int) PartnerWithdrawal::query()
            ->where('partner_id', $partner->id)
            ->whereIn('status', [PartnerWithdrawal::STATUS_PENDING, PartnerWithdrawal::STATUS_PROCESSING])
            ->sum('amount');

        return [
            'total' => $total,
            'on_hold' => $onHold,
            'withdrawn' => $withdrawn,
            'in_progress' => $inProgress,
            'available' => max(0, $total - $onHold - $withdrawn - $inProgress),
        ];
    }

    /**
     * @return array{clicks: int, signups: int, paying: int, active: int}
     */
    public function stats(Partner $partner): array
    {
        $tenants = Tenant::query()->where('referred_by_partner_id', $partner->id)->get();

        return [
            'clicks' => (int) $partner->clicks,
            'signups' => $tenants->count(),
            'paying' => PartnerCommission::query()
                ->where('partner_id', $partner->id)
                ->where('status', PartnerCommission::STATUS_EARNED)
                ->distinct()
                ->count('tenant_id'),
            'active' => $tenants->filter(fn (Tenant $t) => ($t->plan ?? 'trial') !== 'trial' && $t->subscriptionIsActive())->count(),
        ];
    }

    public function submitBank(Partner $partner, string $bankCode, string $bankName, string $accountNumber, string $holder, UploadedFile $proof, UploadedFile $ktp): void
    {
        $store = fn (UploadedFile $file, string $prefix) => $file->storeAs(
            'partner-proofs/'.$partner->id,
            $prefix.'-'.Str::uuid()->toString().'.'.strtolower($file->getClientOriginalExtension() ?: $file->extension()),
            'local'
        );

        $path = $store($proof, 'tabungan');
        $ktpPath = $store($ktp, 'ktp');
        $oldFiles = array_filter([$partner->bank_proof_path, $partner->ktp_path]);

        $partner->forceFill([
            'bank_code' => $bankCode,
            'bank_name' => $bankName,
            'account_number' => $accountNumber,
            'account_holder' => $holder,
            'bank_proof_path' => $path,
            'ktp_path' => $ktpPath,
            'bank_status' => Partner::BANK_PENDING,
            'bank_reject_reason' => null,
            'bank_reviewed_at' => null,
        ])->save();

        Storage::disk('local')->delete(array_diff($oldFiles, [$path, $ktpPath]));
    }

    public function reviewBank(Partner $partner, bool $approve, ?string $reason = null): array
    {
        if ($partner->bank_status !== Partner::BANK_PENDING) {
            return ['ok' => false, 'message' => 'Rekening affiliate ini tidak sedang menunggu verifikasi.'];
        }

        if ($approve && (blank($partner->bank_proof_path) || blank($partner->ktp_path))) {
            return ['ok' => false, 'message' => 'Foto buku tabungan dan KTP wajib ada sebelum rekening disetujui.'];
        }

        $partner->forceFill([
            'bank_status' => $approve ? Partner::BANK_VERIFIED : Partner::BANK_REJECTED,
            'bank_reject_reason' => $approve ? null : $reason,
            'bank_reviewed_at' => now(),
        ])->save();

        return $approve
            ? ['ok' => true, 'message' => "Rekening {$partner->bank_code} {$partner->account_number} a.n. {$partner->account_holder} diverifikasi."]
            : ['ok' => true, 'message' => 'Rekening affiliate ditolak.'];
    }

    public function requestWithdrawal(Partner $partner, int $amount, ?string $note = null): array
    {
        if (! $partner->isActive()) {
            return ['ok' => false, 'message' => 'Akun affiliate belum aktif.'];
        }

        if (! $partner->hasVerifiedBank()) {
            return ['ok' => false, 'message' => 'Rekening tujuan belum diverifikasi admin MikPay.'];
        }

        $min = $this->settings()['min_withdrawal'];
        if ($amount < $min) {
            return ['ok' => false, 'message' => 'Minimal penarikan Rp '.number_format($min, 0, ',', '.').'.'];
        }

        return DB::transaction(function () use ($partner, $amount, $note) {
            $partner = Partner::query()->whereKey($partner->id)->lockForUpdate()->first();

            $available = $this->balance($partner)['available'];
            if ($amount > $available) {
                return ['ok' => false, 'message' => 'Komisi yang bisa ditarik hanya Rp '.number_format($available, 0, ',', '.').'.'];
            }

            $withdrawal = PartnerWithdrawal::query()->create([
                'reference' => 'MT'.now()->format('ymd').strtoupper(Str::random(5)),
                'partner_id' => $partner->id,
                'amount' => $amount,
                'bank_code' => $partner->bank_code,
                'bank_name' => $partner->bank_name,
                'account_number' => $partner->account_number,
                'account_holder' => $partner->account_holder,
                'note' => $note,
                'status' => PartnerWithdrawal::STATUS_PENDING,
            ]);

            $withdrawal->forceFill([
                'xendit_external_id' => 'mpp_'.$partner->id.'_'.$withdrawal->id.'_'.strtolower(Str::random(6)),
            ])->save();

            $next = $this->nextPayoutDate();
            $when = $next ? ' Dana dikirim pada jadwal pencairan '.$next->translatedFormat('d F Y').'.' : ' Admin MikPay akan segera memproses.';

            return ['ok' => true, 'message' => "Permintaan pencairan {$withdrawal->reference} terkirim.".$when];
        });
    }

    public function cancelWithdrawal(PartnerWithdrawal $withdrawal): array
    {
        $updated = PartnerWithdrawal::query()
            ->whereKey($withdrawal->id)
            ->where('status', PartnerWithdrawal::STATUS_PENDING)
            ->update([
                'status' => PartnerWithdrawal::STATUS_CANCELLED,
                'admin_note' => 'Dibatalkan oleh affiliate',
                'updated_at' => now(),
            ]);

        return $updated
            ? ['ok' => true, 'message' => "Penarikan {$withdrawal->reference} dibatalkan."]
            : ['ok' => false, 'message' => 'Penarikan ini sudah diproses.'];
    }

    public function completeWithdrawal(PartnerWithdrawal $withdrawal, string $approver, ?string $reference): array
    {
        $updated = PartnerWithdrawal::query()
            ->whereKey($withdrawal->id)
            ->where('status', PartnerWithdrawal::STATUS_PENDING)
            ->update([
                'status' => PartnerWithdrawal::STATUS_COMPLETED,
                'method' => PartnerWithdrawal::METHOD_MANUAL,
                'transfer_reference' => $reference,
                'reviewed_by' => $approver,
                'reviewed_at' => now(),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

        return $updated
            ? ['ok' => true, 'message' => "Penarikan {$withdrawal->reference} ditandai sudah ditransfer."]
            : ['ok' => false, 'message' => 'Penarikan ini sudah diproses.'];
    }

    public function rejectWithdrawal(PartnerWithdrawal $withdrawal, string $approver, string $reason): array
    {
        $updated = PartnerWithdrawal::query()
            ->whereKey($withdrawal->id)
            ->where('status', PartnerWithdrawal::STATUS_PENDING)
            ->update([
                'status' => PartnerWithdrawal::STATUS_REJECTED,
                'admin_note' => $reason,
                'reviewed_by' => $approver,
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);

        return $updated
            ? ['ok' => true, 'message' => "Penarikan {$withdrawal->reference} ditolak. Komisi kembali ke saldo affiliate."]
            : ['ok' => false, 'message' => 'Penarikan ini sudah diproses.'];
    }

    /**
     * Kirim pencairan affiliate via Xendit Disbursement.
     *
     * @param  int|null  $knownBalance  Saldo Xendit yang sudah dicek pemanggil (batch), agar tidak dicek berulang.
     */
    public function approveViaXendit(PartnerWithdrawal $withdrawal, string $approver, ?int $knownBalance = null): array
    {
        $secret = PaymentGateway::platformXenditSecretKey();
        if ($secret === '') {
            return ['ok' => false, 'message' => 'XENDIT_SECRET_KEY platform belum diatur.'];
        }

        $balance = $knownBalance ?? app(TenantPayoutService::class)->platformXenditBalance();
        if ($balance !== null && $balance < (int) $withdrawal->amount) {
            return ['ok' => false, 'message' => 'Saldo Xendit tidak cukup (Rp '.number_format($balance, 0, ',', '.').') untuk '.$withdrawal->reference.'.'];
        }

        if (! $withdrawal->xendit_external_id) {
            $withdrawal->forceFill([
                'xendit_external_id' => 'mpp_'.$withdrawal->partner_id.'_'.$withdrawal->id.'_'.strtolower(Str::random(6)),
            ])->save();
        }

        $claimed = PartnerWithdrawal::query()
            ->whereKey($withdrawal->id)
            ->where('status', PartnerWithdrawal::STATUS_PENDING)
            ->update([
                'status' => PartnerWithdrawal::STATUS_PROCESSING,
                'method' => PartnerWithdrawal::METHOD_XENDIT,
                'reviewed_by' => $approver,
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);
        if (! $claimed) {
            return ['ok' => false, 'message' => "Pencairan {$withdrawal->reference} sudah diproses."];
        }

        $withdrawal->refresh();

        try {
            $res = Http::withBasicAuth($secret, '')
                ->withHeaders(['X-IDEMPOTENCY-KEY' => $withdrawal->xendit_external_id])
                ->timeout(30)
                ->post(self::XENDIT_API.'/disbursements', [
                    'external_id' => $withdrawal->xendit_external_id,
                    'amount' => (int) $withdrawal->amount,
                    'bank_code' => $withdrawal->bank_code,
                    'account_holder_name' => $withdrawal->account_holder,
                    'account_number' => $withdrawal->account_number,
                    'description' => 'Komisi Affiliate MikPay '.$withdrawal->reference,
                ]);
        } catch (ConnectionException $e) {
            Log::error('partner.disburse.connection', ['id' => $withdrawal->id, 'error' => $e->getMessage()]);
            $withdrawal->forceFill(['admin_note' => 'Koneksi ke Xendit terputus; klik "Cek Status" untuk memastikan.'])->save();

            return ['ok' => false, 'message' => "Koneksi ke Xendit gagal untuk {$withdrawal->reference}. Klik Cek Status sebelum mencoba lagi."];
        }

        $body = (array) $res->json();
        Log::info('partner.disburse.response', ['id' => $withdrawal->id, 'status' => $res->status(), 'body' => $body]);

        if (! $res->successful()) {
            $msg = (string) ($body['message'] ?? $body['error_code'] ?? ('HTTP '.$res->status()));
            $withdrawal->forceFill([
                'status' => PartnerWithdrawal::STATUS_PENDING,
                'method' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'admin_note' => 'Xendit menolak: '.$msg,
            ])->save();

            return ['ok' => false, 'message' => "Xendit menolak {$withdrawal->reference}: ".$msg];
        }

        $this->applyXenditPayload($withdrawal, $body);

        return ['ok' => true, 'message' => "Pencairan {$withdrawal->reference} dikirim ke Xendit (status: ".($body['status'] ?? '-').').'];
    }

    /**
     * Kirim semua pencairan yang menunggu jadwal via Xendit.
     *
     * @return array{ok: bool, message: string}
     */
    public function sendAllPendingViaXendit(string $approver): array
    {
        $pending = PartnerWithdrawal::query()
            ->where('status', PartnerWithdrawal::STATUS_PENDING)
            ->oldest()
            ->get();

        if ($pending->isEmpty()) {
            return ['ok' => false, 'message' => 'Tidak ada pencairan yang menunggu.'];
        }

        $balance = app(TenantPayoutService::class)->platformXenditBalance();
        $total = (int) $pending->sum('amount');
        if ($balance !== null && $balance < $total) {
            return ['ok' => false, 'message' => 'Saldo Xendit Rp '.number_format($balance, 0, ',', '.')
                .' tidak cukup untuk total pencairan Rp '.number_format($total, 0, ',', '.').'. Top up saldo Xendit terlebih dahulu.'];
        }

        $sent = 0;
        $failed = [];
        foreach ($pending as $withdrawal) {
            $result = $this->approveViaXendit($withdrawal, $approver, $balance);
            if ($result['ok']) {
                $sent++;
                $balance = $balance !== null ? $balance - (int) $withdrawal->amount : null;
            } else {
                $failed[] = $result['message'];
            }
        }

        $message = "{$sent} pencairan dikirim ke Xendit.";
        if ($failed !== []) {
            $message .= ' Gagal: '.implode(' | ', $failed);
        }

        return ['ok' => $sent > 0, 'message' => $message];
    }

    public function refreshXenditStatus(PartnerWithdrawal $withdrawal): array
    {
        if (! $withdrawal->isProcessing() || $withdrawal->method !== PartnerWithdrawal::METHOD_XENDIT) {
            return ['ok' => false, 'message' => 'Hanya pencairan via Xendit yang sedang diproses yang bisa dicek.'];
        }

        $secret = PaymentGateway::platformXenditSecretKey();

        try {
            if ($withdrawal->xendit_disbursement_id) {
                $res = Http::withBasicAuth($secret, '')->timeout(20)
                    ->get(self::XENDIT_API.'/disbursements/'.urlencode($withdrawal->xendit_disbursement_id));
                $body = (array) $res->json();
            } else {
                $res = Http::withBasicAuth($secret, '')->timeout(20)
                    ->get(self::XENDIT_API.'/disbursements', ['external_id' => $withdrawal->xendit_external_id]);
                $list = (array) $res->json();
                $body = is_array($list[0] ?? null) ? $list[0] : [];
            }
        } catch (ConnectionException $e) {
            return ['ok' => false, 'message' => 'Gagal menghubungi Xendit: '.$e->getMessage()];
        }

        if (! $res->successful() || $body === []) {
            if (! $withdrawal->xendit_disbursement_id && in_array($res->status(), [200, 404], true)) {
                $withdrawal->forceFill([
                    'status' => PartnerWithdrawal::STATUS_PENDING,
                    'method' => null,
                    'admin_note' => 'Disbursement tidak ditemukan di Xendit; dikembalikan ke antrean.',
                ])->save();

                return ['ok' => true, 'message' => 'Disbursement tidak ditemukan di Xendit. Status dikembalikan ke antrean.'];
            }

            return ['ok' => false, 'message' => 'Xendit: '.($body['message'] ?? ('HTTP '.$res->status()))];
        }

        $this->applyXenditPayload($withdrawal, $body);

        return ['ok' => true, 'message' => 'Status Xendit: '.($body['status'] ?? '-').'.'];
    }

    /**
     * Terapkan payload disbursement (response API / callback) ke pencairan affiliate.
     */
    public function applyXenditPayload(PartnerWithdrawal $withdrawal, array $payload): void
    {
        $xStatus = strtoupper((string) ($payload['status'] ?? ''));
        $fill = [
            'xendit_status' => $xStatus ?: $withdrawal->xendit_status,
            'xendit_disbursement_id' => $payload['id'] ?? $withdrawal->xendit_disbursement_id,
        ];

        if (in_array($withdrawal->status, [PartnerWithdrawal::STATUS_PROCESSING, PartnerWithdrawal::STATUS_PENDING], true)) {
            if ($xStatus === 'COMPLETED') {
                $fill['status'] = PartnerWithdrawal::STATUS_COMPLETED;
                $fill['completed_at'] = now();
            } elseif ($xStatus === 'FAILED') {
                $fill['status'] = PartnerWithdrawal::STATUS_FAILED;
                $fill['failure_code'] = (string) ($payload['failure_code'] ?? '');
                $fill['admin_note'] = 'Transfer gagal: '.($payload['failure_code'] ?? 'unknown').'. Komisi kembali ke saldo affiliate.';
            }
        }

        $withdrawal->forceFill($fill)->save();
    }

    public function findByExternalId(string $externalId): ?PartnerWithdrawal
    {
        return PartnerWithdrawal::query()->where('xendit_external_id', $externalId)->first();
    }
}
