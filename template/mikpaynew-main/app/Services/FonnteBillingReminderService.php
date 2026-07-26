<?php

namespace App\Services;

use App\Helpers\BillingArrearsHelper;
use App\Helpers\BillingStatusHelper;
use App\Models\BillingCustomer;
use App\Models\BillingPeriodPromise;
use App\Models\FonnteReminderDispatch;
use App\Models\FonnteSetting;
use App\Models\MikpayRouter;
use App\Models\User;
use App\Support\SubscriptionGate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FonnteBillingReminderService
{
    public function __construct(
        private FonnteApiService $fonnteApi
    ) {}

    /**
     * @return array{sent: int, skipped: int, failed: int, users: int}
     */
    public function runAll(bool $dryRun = false): array
    {
        $totals = ['sent' => 0, 'skipped' => 0, 'failed' => 0, 'users' => 0];

        $query = FonnteSetting::query()
            ->with('user')
            ->where('enabled', true)
            ->whereNotNull('api_token');

        foreach ($query->cursor() as $fonnte) {
            /** @var FonnteSetting $fonnte */
            $user = $fonnte->user;
            if (! $user instanceof User) {
                continue;
            }

            if ($user->status !== 'active') {
                continue;
            }

            if (! SubscriptionGate::allowsAppAccess($user)) {
                continue;
            }

            if (! $fonnte->isNowWithinSendWindow()) {
                continue;
            }

            $totals['users']++;
            $batch = $this->runForTenant($fonnte, $user, $dryRun);
            $totals['sent'] += $batch['sent'];
            $totals['skipped'] += $batch['skipped'];
            $totals['failed'] += $batch['failed'];
        }

        return $totals;
    }

    /**
     * @return array{sent: int, skipped: int, failed: int}
     */
    public function runForTenant(FonnteSetting $fonnte, User $user, bool $dryRun = false): array
    {
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        $routerSessions = MikpayRouter::withoutGlobalScope('owner')
            ->where('user_id', $user->id)
            ->where('status', 'enable')
            ->pluck('session_name')
            ->filter()
            ->values()
            ->all();

        $customers = BillingCustomer::query()
            ->where(function ($q) use ($routerSessions) {
                $q->whereNull('router_session');
                if ($routerSessions !== []) {
                    $q->orWhereIn('router_session', $routerSessions);
                }
            })
            ->orderBy('customer_name')
            ->get();

        $today = now()->toDateString();
        $currentPeriod = now()->format('Y-m');
        $promiseRows = collect();
        if ($routerSessions !== []) {
            $promiseRows = BillingPeriodPromise::query()
                ->where('period', $currentPeriod)
                ->whereIn('router_session', $routerSessions)
                ->get();
        }

        foreach ($customers as $customer) {
            $customerName = (string) $customer->customer_name;
            $dueDay = (int) $customer->due_day;
            $monthlyFee = (int) ($customer->monthly_fee ?? 0);
            $routerSession = $customer->router_session ? (string) $customer->router_session : null;

            $candidates = $promiseRows->where('customer_name', $customerName);
            $promRow = ($routerSession !== null && $routerSession !== '')
                ? $candidates->firstWhere('router_session', $routerSession)
                : $candidates->first();
            $promisedPayDate = $promRow?->promised_pay_date?->format('Y-m-d');

            $status = BillingStatusHelper::getStatus(
                $customerName,
                $dueDay,
                $promisedPayDate
            );

            $hasPeriodPromise = $promisedPayDate !== null && $promisedPayDate !== '';

            $kinds = [];
            if ($hasPeriodPromise) {
                // Janji bayar: hanya WhatsApp pada hari tanggal janji (template H-0), tanpa H-3 dan tanpa pengingat tunggakan.
                if ($fonnte->auto_send_h0
                    && $status['status'] === 'due_today'
                    && ! empty($status['is_promised'])) {
                    $kinds[] = FonnteReminderDispatch::KIND_H0;
                }
            } else {
                if ($fonnte->auto_send_h3 && $status['status'] === 'due_soon' && (int) ($status['days_to_due'] ?? -1) === 3) {
                    $kinds[] = FonnteReminderDispatch::KIND_H3;
                }
                if ($fonnte->auto_send_h0 && $status['status'] === 'due_today') {
                    $kinds[] = FonnteReminderDispatch::KIND_H0;
                }
                if ($fonnte->auto_send_overdue && $status['status'] === 'overdue') {
                    $kinds[] = FonnteReminderDispatch::KIND_OVERDUE;
                }
            }

            if ($kinds === []) {
                continue;
            }

            $target = $this->normalizeIndonesiaWhatsapp($customer->phone ?? '');
            if ($target === null) {
                $skipped += count($kinds);
                continue;
            }

            foreach ($kinds as $kind) {
                if (FonnteReminderDispatch::query()
                    ->where('user_id', $user->id)
                    ->where('customer_name', $customerName)
                    ->where('kind', $kind)
                    ->where('dispatch_date', $today)
                    ->exists()) {
                    $skipped++;
                    continue;
                }

                $useJanjiTemplate = $hasPeriodPromise && $kind === FonnteReminderDispatch::KIND_H0;
                $template = $this->resolveTemplate($fonnte, $kind, $useJanjiTemplate);
                if ($template === null || trim($template) === '') {
                    $skipped++;
                    continue;
                }

                $arrears = BillingArrearsHelper::getUnpaidPeriodsSummary(
                    $customerName,
                    $dueDay,
                    $monthlyFee,
                    $routerSession
                );
                $nominalInt = $kind === FonnteReminderDispatch::KIND_OVERDUE
                    ? (int) ($arrears['outstanding_total'] ?? $monthlyFee)
                    : $monthlyFee;
                if ($nominalInt <= 0) {
                    $nominalInt = $monthlyFee;
                }

                $hariStr = $this->resolveHariPlaceholder($kind, $status);
                $message = $this->composeMessage($fonnte, $template, [
                    'nama' => trim((string) ($customer->display_name ?: $customerName)),
                    'nominal' => $this->formatRupiah($nominalInt),
                    'tanggal' => $this->formatTanggal($status['due_date'] ?? null),
                    'hari' => $hariStr,
                    'pengirim' => trim((string) ($fonnte->sender_name ?: $user->business_name ?: $user->name ?: 'MIKPAY')),
                ]);

                if ($dryRun) {
                    $sent++;
                    continue;
                }

                $result = $this->fonnteApi->sendText((string) $fonnte->api_token, $target, $message);
                if ($result['ok']) {
                    FonnteReminderDispatch::create([
                        'user_id' => $user->id,
                        'customer_name' => $customerName,
                        'kind' => $kind,
                        'dispatch_date' => $today,
                    ]);
                    $sent++;
                    $this->applyAntispamDelay($fonnte);
                } else {
                    $failed++;
                    Log::warning('fonnte.reminder.send_failed', [
                        'user_id' => $user->id,
                        'customer' => $customerName,
                        'kind' => $kind,
                        'response' => $result['body'],
                    ]);
                }
            }
        }

        return ['sent' => $sent, 'skipped' => $skipped, 'failed' => $failed];
    }

    /**
     * @param  bool  $forJanjiBayar  Pengingat hari janji (masih kind H0); pakai template_janji_bayar jika diisi, selain itu sama seperti H-0 biasa.
     */
    private function resolveTemplate(FonnteSetting $fonnte, string $kind, bool $forJanjiBayar = false): ?string
    {
        if ($forJanjiBayar && $kind === FonnteReminderDispatch::KIND_H0) {
            $janji = trim((string) ($fonnte->template_janji_bayar ?? ''));
            if ($janji !== '') {
                return $janji;
            }

            return $fonnte->template_due_today;
        }

        return match ($kind) {
            FonnteReminderDispatch::KIND_H3 => $fonnte->template_reminder,
            FonnteReminderDispatch::KIND_H0 => $fonnte->template_due_today,
            FonnteReminderDispatch::KIND_OVERDUE => $fonnte->template_overdue,
            default => null,
        };
    }

    /**
     * @param  array{nama: string, nominal: string, tanggal: string, hari: string, pengirim: string}  $vars
     */
    private function composeMessage(FonnteSetting $fonnte, string $template, array $vars): string
    {
        $body = str_replace(
            ['{nama}', '{nominal}', '{tanggal}', '{hari}', '{pengirim}'],
            [$vars['nama'], $vars['nominal'], $vars['tanggal'], $vars['hari'], $vars['pengirim']],
            $template
        );

        $parts = [];
        $greetings = is_array($fonnte->template_greetings) ? array_values(array_filter($fonnte->template_greetings, fn ($v) => is_string($v) && trim($v) !== '')) : [];
        $closings = is_array($fonnte->template_closings) ? array_values(array_filter($fonnte->template_closings, fn ($v) => is_string($v) && trim($v) !== '')) : [];

        if ($greetings !== []) {
            $parts[] = trim((string) $greetings[array_rand($greetings)]);
        }
        $parts[] = trim($body);
        if ($closings !== []) {
            $parts[] = trim((string) $closings[array_rand($closings)]);
        }

        return implode("\n\n", array_filter($parts, fn ($p) => $p !== ''));
    }

    private function resolveHariPlaceholder(string $kind, array $status): string
    {
        $d = $status['days_to_due'];

        return match ($kind) {
            FonnteReminderDispatch::KIND_H3 => (string) max(0, (int) $d),
            FonnteReminderDispatch::KIND_H0 => '0',
            FonnteReminderDispatch::KIND_OVERDUE => (string) max(0, abs((int) $d)),
            default => '',
        };
    }

    private function formatRupiah(int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    private function formatTanggal(?string $dueDate): string
    {
        if ($dueDate === null || $dueDate === '') {
            return '-';
        }
        try {
            return Carbon::parse($dueDate)->locale(app()->getLocale())->translatedFormat('d F Y');
        } catch (\Throwable) {
            return $dueDate;
        }
    }

    public function normalizeIndonesiaWhatsapp(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '62')) {
            return $digits;
        }
        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }
        if (str_starts_with($digits, '8')) {
            return '62' . $digits;
        }

        return strlen($digits) >= 10 ? $digits : null;
    }

    private function applyAntispamDelay(FonnteSetting $fonnte): void
    {
        if (! $fonnte->antispam_enabled) {
            return;
        }
        $min = max(0, (int) $fonnte->antispam_delay_min);
        $max = max($min, (int) $fonnte->antispam_delay_max);
        $sec = $min === $max ? $min : random_int($min, $max);
        if ($sec > 0) {
            usleep($sec * 1_000_000);
        }
    }
}
