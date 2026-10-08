<?php

namespace App\Services;

use App\Models\BillingPayment;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\TenantPayoutAccount;
use App\Models\TenantWithdrawal;
use App\Models\User;
use App\Models\VoucherOrder;
use App\Support\PaymentGateway;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Penarikan dana tenant atas pembayaran pelanggan yang masuk ke Xendit platform MikPay.
 */
class TenantPayoutService
{
    private const API = 'https://api.xendit.co';

    private const BANKS_CACHE_KEY = 'mikpay.xendit.disbursement_banks';

    public const DEFAULT_SETTINGS = [
        'enabled' => true,
        'min_amount' => 50000,
        'fee_flat' => 0,
        'fee_percent' => 0,
        'hold_days' => 1,
    ];

    /**
     * @return array{enabled: bool, min_amount: int, fee_flat: int, fee_percent: float, hold_days: int}
     */
    public function settings(): array
    {
        $raw = PlatformSetting::get()->payout_settings;
        $s = array_merge(self::DEFAULT_SETTINGS, is_array($raw) ? $raw : []);

        return [
            'enabled' => (bool) $s['enabled'],
            'min_amount' => max(10000, (int) $s['min_amount']),
            'fee_flat' => max(0, (int) $s['fee_flat']),
            'fee_percent' => max(0, min(100, round((float) $s['fee_percent'], 2))),
            'hold_days' => max(0, min(30, (int) $s['hold_days'])),
        ];
    }

    public function saveSettings(array $data): void
    {
        $setting = PlatformSetting::get();
        $existing = is_array($setting->payout_settings) ? $setting->payout_settings : [];
        $setting->payout_settings = [
            ...$existing,
            'enabled' => (bool) ($data['enabled'] ?? false),
            'min_amount' => (int) $data['min_amount'],
            'fee_flat' => (int) $data['fee_flat'],
            'fee_percent' => (float) $data['fee_percent'],
            'hold_days' => (int) $data['hold_days'],
        ];
        $setting->save();
    }

    public function feeFor(int $amount): int
    {
        $s = $this->settings();

        return $s['fee_flat'] + (int) round($amount * $s['fee_percent'] / 100);
    }

    /**
     * Ringkasan saldo tenant (semua angka dalam rupiah).
     *
     * @return array{collected: int, on_hold: int, withdrawn: int, in_progress: int, available: int}
     */
    public function balance(Tenant $tenant): array
    {
        $base = BillingPayment::query()
            ->where('tenant_id', $tenant->id)
            ->where('payment_method', 'Xendit')
            ->where('gateway', PaymentGateway::ACTIVE_PLATFORM);

        $vouchers = VoucherOrder::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', VoucherOrder::PAID_STATUSES);

        $voucherCollected = (int) (clone $vouchers)->sum('price');
        $collected = (int) (clone $base)->sum('amount') + $voucherCollected;

        $holdDays = $this->settings()['hold_days'];
        $onHold = $holdDays > 0
            ? (int) (clone $base)->where('payment_date', '>', now()->subDays($holdDays))->sum('amount')
                + (int) (clone $vouchers)->where('paid_at', '>', now()->subDays($holdDays))->sum('price')
            : 0;

        $withdrawn = (int) TenantWithdrawal::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', TenantWithdrawal::STATUS_COMPLETED)
            ->sum('amount');

        $inProgress = (int) TenantWithdrawal::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', [TenantWithdrawal::STATUS_PENDING, TenantWithdrawal::STATUS_PROCESSING])
            ->sum('amount');

        return [
            'collected' => $collected,
            'voucher_collected' => $voucherCollected,
            'on_hold' => $onHold,
            'withdrawn' => $withdrawn,
            'in_progress' => $inProgress,
            'available' => max(0, $collected - $onHold - $withdrawn - $inProgress),
        ];
    }

    /**
     * Pengajuan rekening terbaru tenant (bisa pending / verified / rejected).
     */
    public function currentAccount(Tenant $tenant): ?TenantPayoutAccount
    {
        return TenantPayoutAccount::query()
            ->where('tenant_id', $tenant->id)
            ->latest('id')
            ->first();
    }

    /**
     * Rekening yang boleh dipakai menarik dana: pengajuan terbaru dan sudah diverifikasi admin.
     */
    public function verifiedAccount(Tenant $tenant): ?TenantPayoutAccount
    {
        $account = $this->currentAccount($tenant);

        return $account?->isVerified() ? $account : null;
    }

    /**
     * Ajukan rekening baru + foto buku tabungan. Pengajuan pending sebelumnya diganti.
     */
    public function submitAccount(
        Tenant $tenant,
        User $user,
        string $bankCode,
        string $accountNumber,
        string $holder,
        UploadedFile $proof
    ): TenantPayoutAccount {
        $path = $proof->storeAs(
            'payout-proofs/'.$tenant->id,
            Str::uuid()->toString().'.'.strtolower($proof->getClientOriginalExtension() ?: $proof->extension()),
            'local'
        );

        return DB::transaction(function () use ($tenant, $user, $bankCode, $accountNumber, $holder, $path) {
            TenantPayoutAccount::query()
                ->where('tenant_id', $tenant->id)
                ->where('status', TenantPayoutAccount::STATUS_PENDING)
                ->get()
                ->each(function (TenantPayoutAccount $old) {
                    Storage::disk('local')->delete($old->proof_path);
                    $old->delete();
                });

            return TenantPayoutAccount::query()->create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'bank_code' => $bankCode,
                'bank_name' => $this->bankName($bankCode),
                'account_number' => $accountNumber,
                'account_holder' => $holder,
                'proof_path' => $path,
                'status' => TenantPayoutAccount::STATUS_PENDING,
            ]);
        });
    }

    public function verifyAccount(TenantPayoutAccount $account, string $approver): array
    {
        $updated = TenantPayoutAccount::query()
            ->whereKey($account->id)
            ->where('status', TenantPayoutAccount::STATUS_PENDING)
            ->update([
                'status' => TenantPayoutAccount::STATUS_VERIFIED,
                'reject_reason' => null,
                'reviewed_by' => $approver,
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);

        return $updated
            ? ['ok' => true, 'message' => "Rekening {$account->bank_code} {$account->account_number} a.n. {$account->account_holder} diverifikasi."]
            : ['ok' => false, 'message' => 'Pengajuan rekening ini sudah diproses.'];
    }

    public function rejectAccount(TenantPayoutAccount $account, string $approver, string $reason): array
    {
        $updated = TenantPayoutAccount::query()
            ->whereKey($account->id)
            ->where('status', TenantPayoutAccount::STATUS_PENDING)
            ->update([
                'status' => TenantPayoutAccount::STATUS_REJECTED,
                'reject_reason' => $reason,
                'reviewed_by' => $approver,
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);

        return $updated
            ? ['ok' => true, 'message' => "Pengajuan rekening {$account->account_number} ditolak."]
            : ['ok' => false, 'message' => 'Pengajuan rekening ini sudah diproses.'];
    }

    /**
     * Daftar bank/e-wallet tujuan disbursement Xendit: [code => name].
     *
     * @return array<string, string>
     */
    public function banks(): array
    {
        $cached = Cache::get(self::BANKS_CACHE_KEY);
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        $banks = [];
        $secret = PaymentGateway::platformXenditSecretKey();
        if ($secret !== '') {
            try {
                $res = Http::withBasicAuth($secret, '')->timeout(15)->get(self::API.'/available_disbursements_banks');
                foreach ((array) $res->json() as $row) {
                    if (is_array($row) && ! empty($row['code']) && ($row['can_disburse'] ?? true)) {
                        $banks[(string) $row['code']] = (string) ($row['name'] ?? $row['code']);
                    }
                }
            } catch (Throwable $e) {
                Log::warning('payout.banks.fetch_failed', ['error' => $e->getMessage()]);
            }
        }

        if ($banks === []) {
            return $this->fallbackBanks();
        }

        uksort($banks, function (string $a, string $b) {
            $top = array_keys($this->fallbackBanks());
            $ia = array_search($a, $top, true);
            $ib = array_search($b, $top, true);
            if ($ia !== false || $ib !== false) {
                return ($ia === false ? 999 : $ia) <=> ($ib === false ? 999 : $ib);
            }

            return 0;
        });

        Cache::put(self::BANKS_CACHE_KEY, $banks, now()->addDay());

        return $banks;
    }

    public function bankName(string $code): string
    {
        return $this->banks()[$code] ?? $code;
    }

    /**
     * @return array{ok: bool, message: string, withdrawal?: TenantWithdrawal}
     */
    public function requestWithdrawal(Tenant $tenant, User $user, int $amount, ?string $note = null): array
    {
        $settings = $this->settings();
        if (! $settings['enabled']) {
            return ['ok' => false, 'message' => 'Fitur penarikan dana sedang dinonaktifkan oleh admin MikPay.'];
        }

        $bank = $this->verifiedAccount($tenant);
        if (! $bank) {
            return ['ok' => false, 'message' => 'Rekening tujuan belum diverifikasi admin MikPay.'];
        }

        if ($amount < $settings['min_amount']) {
            return ['ok' => false, 'message' => 'Minimal penarikan Rp '.number_format($settings['min_amount'], 0, ',', '.').'.'];
        }

        $fee = $this->feeFor($amount);
        if ($amount - $fee < 10000) {
            return ['ok' => false, 'message' => 'Nominal setelah biaya admin terlalu kecil.'];
        }

        return DB::transaction(function () use ($tenant, $user, $amount, $fee, $bank, $note) {
            Tenant::query()->whereKey($tenant->id)->lockForUpdate()->first();

            $available = $this->balance($tenant)['available'];
            if ($amount > $available) {
                return ['ok' => false, 'message' => 'Saldo yang bisa ditarik hanya Rp '.number_format($available, 0, ',', '.').'.'];
            }

            $withdrawal = TenantWithdrawal::query()->create([
                'reference' => 'WD'.now()->format('ymd').strtoupper(Str::random(5)),
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'payout_account_id' => $bank->id,
                'amount' => $amount,
                'fee' => $fee,
                'net_amount' => $amount - $fee,
                'bank_code' => $bank->bank_code,
                'bank_name' => $bank->bank_name,
                'account_number' => $bank->account_number,
                'account_holder' => $bank->account_holder,
                'note' => $note,
                'status' => TenantWithdrawal::STATUS_PENDING,
            ]);

            $withdrawal->forceFill([
                'xendit_external_id' => 'mpw_'.$tenant->id.'_'.$withdrawal->id.'_'.strtolower(Str::random(6)),
            ])->save();

            return ['ok' => true, 'message' => "Permintaan penarikan {$withdrawal->reference} dikirim. Menunggu konfirmasi admin MikPay.", 'withdrawal' => $withdrawal];
        });
    }

    public function cancel(TenantWithdrawal $withdrawal, User $user): array
    {
        $updated = TenantWithdrawal::query()
            ->whereKey($withdrawal->id)
            ->where('status', TenantWithdrawal::STATUS_PENDING)
            ->update([
                'status' => TenantWithdrawal::STATUS_CANCELLED,
                'admin_note' => 'Dibatalkan oleh '.$user->email,
                'updated_at' => now(),
            ]);

        return $updated
            ? ['ok' => true, 'message' => "Penarikan {$withdrawal->reference} dibatalkan."]
            : ['ok' => false, 'message' => 'Penarikan ini sudah diproses dan tidak bisa dibatalkan.'];
    }

    /**
     * Setujui & kirim dana via Xendit Disbursement.
     */
    public function approveViaXendit(TenantWithdrawal $withdrawal, string $approver): array
    {
        $secret = PaymentGateway::platformXenditSecretKey();
        if ($secret === '') {
            return ['ok' => false, 'message' => 'XENDIT_SECRET_KEY platform belum diatur.'];
        }

        $xenditBalance = $this->platformXenditBalance();
        if ($xenditBalance !== null && $xenditBalance < (int) $withdrawal->net_amount) {
            return ['ok' => false, 'message' => 'Saldo Xendit platform tidak cukup (Rp '.number_format($xenditBalance, 0, ',', '.').'). Top up / tunggu settlement, atau transfer manual.'];
        }

        $claimed = TenantWithdrawal::query()
            ->whereKey($withdrawal->id)
            ->where('status', TenantWithdrawal::STATUS_PENDING)
            ->update([
                'status' => TenantWithdrawal::STATUS_PROCESSING,
                'method' => TenantWithdrawal::METHOD_XENDIT,
                'reviewed_by' => $approver,
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);
        if (! $claimed) {
            return ['ok' => false, 'message' => 'Penarikan ini sudah diproses.'];
        }

        $withdrawal->refresh();

        try {
            $res = Http::withBasicAuth($secret, '')
                ->withHeaders(['X-IDEMPOTENCY-KEY' => $withdrawal->xendit_external_id])
                ->timeout(30)
                ->post(self::API.'/disbursements', [
                    'external_id' => $withdrawal->xendit_external_id,
                    'amount' => (int) $withdrawal->net_amount,
                    'bank_code' => $withdrawal->bank_code,
                    'account_holder_name' => $withdrawal->account_holder,
                    'account_number' => $withdrawal->account_number,
                    'description' => 'MikPay payout '.$withdrawal->reference,
                ]);
        } catch (ConnectionException $e) {
            Log::error('payout.disburse.connection', ['id' => $withdrawal->id, 'error' => $e->getMessage()]);
            $withdrawal->forceFill(['admin_note' => 'Koneksi ke Xendit terputus; klik "Cek Status" untuk memastikan.'])->save();

            return ['ok' => false, 'message' => 'Koneksi ke Xendit gagal. Status ditandai "Sedang Ditransfer" — klik Cek Status untuk memastikan sebelum mencoba lagi.'];
        }

        $body = (array) $res->json();
        Log::info('payout.disburse.response', ['id' => $withdrawal->id, 'status' => $res->status(), 'body' => $body]);

        if (! $res->successful()) {
            $msg = (string) ($body['message'] ?? $body['error_code'] ?? ('HTTP '.$res->status()));
            $withdrawal->forceFill([
                'status' => TenantWithdrawal::STATUS_PENDING,
                'method' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'admin_note' => 'Xendit menolak: '.$msg,
            ])->save();

            return ['ok' => false, 'message' => 'Xendit menolak disbursement: '.$msg];
        }

        $this->applyXenditPayload($withdrawal, $body);

        return ['ok' => true, 'message' => "Disbursement {$withdrawal->reference} dikirim ke Xendit (status: ".($body['status'] ?? '-').').'];
    }

    public function markManual(TenantWithdrawal $withdrawal, string $approver, ?string $reference): array
    {
        $updated = TenantWithdrawal::query()
            ->whereKey($withdrawal->id)
            ->where('status', TenantWithdrawal::STATUS_PENDING)
            ->update([
                'status' => TenantWithdrawal::STATUS_COMPLETED,
                'method' => TenantWithdrawal::METHOD_MANUAL,
                'transfer_reference' => $reference,
                'reviewed_by' => $approver,
                'reviewed_at' => now(),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

        return $updated
            ? ['ok' => true, 'message' => "Penarikan {$withdrawal->reference} ditandai sudah ditransfer manual."]
            : ['ok' => false, 'message' => 'Penarikan ini sudah diproses.'];
    }

    public function reject(TenantWithdrawal $withdrawal, string $approver, string $reason): array
    {
        $updated = TenantWithdrawal::query()
            ->whereKey($withdrawal->id)
            ->where('status', TenantWithdrawal::STATUS_PENDING)
            ->update([
                'status' => TenantWithdrawal::STATUS_REJECTED,
                'admin_note' => $reason,
                'reviewed_by' => $approver,
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);

        return $updated
            ? ['ok' => true, 'message' => "Penarikan {$withdrawal->reference} ditolak. Saldo kembali ke tenant."]
            : ['ok' => false, 'message' => 'Penarikan ini sudah diproses.'];
    }

    /**
     * Sinkron status dari API Xendit (fallback bila callback belum masuk).
     */
    public function refreshStatus(TenantWithdrawal $withdrawal): array
    {
        if (! $withdrawal->isProcessing() || $withdrawal->method !== TenantWithdrawal::METHOD_XENDIT) {
            return ['ok' => false, 'message' => 'Hanya penarikan via Xendit yang sedang diproses yang bisa dicek.'];
        }

        $secret = PaymentGateway::platformXenditSecretKey();

        try {
            if ($withdrawal->xendit_disbursement_id) {
                $res = Http::withBasicAuth($secret, '')->timeout(20)
                    ->get(self::API.'/disbursements/'.urlencode($withdrawal->xendit_disbursement_id));
                $body = (array) $res->json();
            } else {
                $res = Http::withBasicAuth($secret, '')->timeout(20)
                    ->get(self::API.'/disbursements', ['external_id' => $withdrawal->xendit_external_id]);
                $list = (array) $res->json();
                $body = is_array($list[0] ?? null) ? $list[0] : [];
            }
        } catch (ConnectionException $e) {
            return ['ok' => false, 'message' => 'Gagal menghubungi Xendit: '.$e->getMessage()];
        }

        if (! $res->successful() || $body === []) {
            if (! $withdrawal->xendit_disbursement_id && in_array($res->status(), [200, 404], true)) {
                // Tidak pernah tercatat di Xendit → kembalikan ke antrean agar bisa diproses ulang.
                $withdrawal->forceFill([
                    'status' => TenantWithdrawal::STATUS_PENDING,
                    'method' => null,
                    'admin_note' => 'Disbursement tidak ditemukan di Xendit; dikembalikan ke antrean.',
                ])->save();

                return ['ok' => true, 'message' => 'Disbursement tidak ditemukan di Xendit. Status dikembalikan ke "Menunggu Konfirmasi".'];
            }

            return ['ok' => false, 'message' => 'Xendit: '.($body['message'] ?? ('HTTP '.$res->status()))];
        }

        $this->applyXenditPayload($withdrawal, $body);

        return ['ok' => true, 'message' => 'Status Xendit: '.($body['status'] ?? '-').'.'];
    }

    /**
     * Terapkan payload disbursement (response API / callback) ke record.
     */
    public function applyXenditPayload(TenantWithdrawal $withdrawal, array $payload): void
    {
        $xStatus = strtoupper((string) ($payload['status'] ?? ''));
        $fill = [
            'xendit_status' => $xStatus ?: $withdrawal->xendit_status,
            'xendit_disbursement_id' => $payload['id'] ?? $withdrawal->xendit_disbursement_id,
        ];

        if (in_array($withdrawal->status, [TenantWithdrawal::STATUS_PROCESSING, TenantWithdrawal::STATUS_PENDING], true)) {
            if ($xStatus === 'COMPLETED') {
                $fill['status'] = TenantWithdrawal::STATUS_COMPLETED;
                $fill['completed_at'] = now();
            } elseif ($xStatus === 'FAILED') {
                $fill['status'] = TenantWithdrawal::STATUS_FAILED;
                $fill['failure_code'] = (string) ($payload['failure_code'] ?? '');
                $fill['admin_note'] = 'Transfer gagal: '.($payload['failure_code'] ?? 'unknown').'. Saldo kembali ke tenant.';
            }
        }

        $withdrawal->forceFill($fill)->save();
    }

    public function findByExternalId(string $externalId): ?TenantWithdrawal
    {
        return TenantWithdrawal::query()->where('xendit_external_id', $externalId)->first();
    }

    public function platformXenditBalance(): ?int
    {
        $secret = PaymentGateway::platformXenditSecretKey();
        if ($secret === '') {
            return null;
        }

        try {
            $res = Http::withBasicAuth($secret, '')->timeout(10)->get(self::API.'/balance');

            return $res->successful() ? (int) ($res->json('balance') ?? 0) : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, string>
     */
    private function fallbackBanks(): array
    {
        return [
            'BCA' => 'Bank Central Asia (BCA)',
            'MANDIRI' => 'Bank Mandiri',
            'BRI' => 'Bank Rakyat Indonesia (BRI)',
            'BNI' => 'Bank Negara Indonesia (BNI)',
            'BSI' => 'Bank Syariah Indonesia (BSI)',
            'CIMB' => 'CIMB Niaga',
            'PERMATA' => 'Bank Permata',
            'DANAMON' => 'Bank Danamon',
            'BTN' => 'Bank Tabungan Negara (BTN)',
            'SEABANK' => 'SeaBank',
            'JAGO' => 'Bank Jago',
            'DANA' => 'DANA',
            'OVO' => 'OVO',
            'GOPAY' => 'GoPay',
            'SHOPEEPAY' => 'ShopeePay',
        ];
    }
}
