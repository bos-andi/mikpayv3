<?php

namespace App\Http\Controllers;

use App\Helpers\BillingArrearsHelper;
use App\Helpers\BillingStatusHelper;
use App\Models\BusinessSetting;
use App\Models\BillingCustomer;
use App\Models\BillingPayment;
use App\Models\BillingPeriodPromise;
use App\Models\CashTransaction;
use App\Models\MikpayRouter;
use App\Services\BillingCustomerTemplateService;
use App\Services\MikrotikService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PppController extends Controller
{
    private function currentUserId(): int
    {
        return (int) (auth()->id() ?? 0);
    }

    private function resolveLetterheadDataUri(BusinessSetting $business): ?string
    {
        $path = null;

        $uploaded = trim((string) ($business->letterhead_image ?? ''));
        if ($uploaded !== '') {
            $candidate = public_path('mikpay/img/' . $uploaded);
            if (File::exists($candidate)) {
                $path = $candidate;
            }
        }

        if (! $path) {
            $fallback = public_path('mikpay/img/kop-andidev.png');
            if (File::exists($fallback)) {
                $path = $fallback;
            }
        }

        if (! $path || ! File::exists($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';
        $raw = File::get($path);
        return 'data:' . $mime . ';base64,' . base64_encode($raw);
    }

    private function formatInvoiceId(string $period, int $id): string
    {
        // INV-202603-000123
        $yyyymm = preg_replace('/[^0-9]/', '', $period);
        $yyyymm = str_pad($yyyymm, 6, '0', STR_PAD_LEFT);
        return 'INV-' . $yyyymm . '-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    private function mapPaymentMethodToCashMetode(?string $paymentMethod): string
    {
        $m = strtolower(trim($paymentMethod ?? ''));
        if (in_array($m, ['tunai', 'cash'])) {
            return 'tunai';
        }
        if (in_array($m, ['transfer', 'bank'])) {
            return 'transfer';
        }
        return 'lainnya';
    }

    private function ensureRouter(Request $request): array|RedirectResponse
    {
        $userId = $this->currentUserId();
        $sessionName = $request->session()->get('selected_router');
        if (! $sessionName) {
            return redirect()->route('sessions');
        }
        $router = MikpayRouter::where('user_id', $userId)->where('session_name', $sessionName)->where('status', 'enable')->first();
        if (! $router) {
            $request->session()->forget('selected_router');
            return redirect()->route('sessions')->with('error', 'Router tidak ditemukan.');
        }
        $mikrotik = new MikrotikService;
        if (! $mikrotik->connectRouter($router)) {
            $mikrotik->disconnect();
            return redirect()->route('sessions')->with('error', 'Tidak bisa connect ke router.');
        }
        $identity = $router->router_name ?: $sessionName;
        $clock = ['time' => date('H:i:s'), 'date' => date('d M Y'), 'time-zone-name' => date_default_timezone_get()];
        try {
            $getIdentity = $mikrotik->comm('/system/identity/print');
            if (is_array($getIdentity) && isset($getIdentity[0]['name'])) {
                $identity = $getIdentity[0]['name'];
            }
            $getClock = $mikrotik->comm('/system/clock/print');
            if (is_array($getClock) && isset($getClock[0])) {
                $clock = $getClock[0];
            }
        } catch (\Throwable $e) {
            // keep defaults
        }
        $routers = MikpayRouter::where('user_id', $userId)->where('status', 'enable')->orderBy('session_name')->get();
        return [
            'currentRouter' => $router,
            'routers' => $routers,
            'identity' => $identity,
            'clock' => $clock,
            'mikrotik' => $mikrotik,
        ];
    }

    /** PPP Secrets list + online status */
    public function secrets(Request $request): View|RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $mikrotik = $ctx['mikrotik'];

        $secrets = [];
        $onlineUsers = [];
        $profiles = [];
        try {
            $secrets = $mikrotik->comm('/ppp/secret/print');
            $secrets = is_array($secrets) ? $secrets : [];
            $activeList = $mikrotik->comm('/ppp/active/print');
            $activeList = is_array($activeList) ? $activeList : [];
            $profiles = $mikrotik->comm('/ppp/profile/print');
            $profiles = is_array($profiles) ? $profiles : [];
            foreach ($activeList as $a) {
                $onlineUsers[$a['name'] ?? ''] = true;
            }
        } catch (\Throwable $e) {
            report($e);
        }
        $mikrotik->disconnect();

        return view('ppp.secrets', array_merge($ctx, [
            'secrets' => $secrets,
            'onlineUsers' => $onlineUsers,
            'profiles' => $profiles,
        ]));
    }

    /** Simpan PPP Secret */
    public function storeSecret(Request $request): RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $mikrotik = $ctx['mikrotik'];

        $valid = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'service' => 'nullable|string|in:any,async,l2tp,ovpn,pppoe,pptp,sstp',
            'profile' => 'required|string|max:255',
        ]);

        $params = [
            'name' => $valid['name'],
            'password' => $valid['password'],
            'service' => $valid['service'] ?? 'any',
            'profile' => $valid['profile'],
        ];
        if ($request->filled('local-address')) {
            $params['local-address'] = $request->input('local-address');
        }
        if ($request->filled('remote-address')) {
            $params['remote-address'] = $request->input('remote-address');
        }
        if ($request->filled('comment')) {
            $params['comment'] = $request->input('comment');
        }

        try {
            $mikrotik->comm('/ppp/secret/add', $params);
        } catch (\Throwable $e) {
            report($e);
            $mikrotik->disconnect();
            return redirect()->route('ppp.secrets')->with('error', 'Gagal menambah secret: ' . $e->getMessage());
        }
        $mikrotik->disconnect();

        return redirect()->route('ppp.secrets')->with('success', 'PPP Secret berhasil ditambah.');
    }

    /** Update PPP Secret */
    public function updateSecret(Request $request, string $id): RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $mikrotik = $ctx['mikrotik'];

        $valid = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'nullable|string|max:255',
            'service' => 'nullable|string|in:any,async,l2tp,ovpn,pppoe,pptp,sstp',
            'profile' => 'required|string|max:255',
        ]);

        $params = [
            '.id' => $id,
            'name' => $valid['name'],
            'service' => $valid['service'] ?? 'any',
            'profile' => $valid['profile'],
            'local-address' => (string) $request->input('local-address', ''),
            'remote-address' => (string) $request->input('remote-address', ''),
            'comment' => (string) $request->input('comment', ''),
        ];
        if ($request->filled('password')) {
            $params['password'] = (string) $request->string('password');
        }

        try {
            $mikrotik->comm('/ppp/secret/set', $params);
        } catch (\Throwable $e) {
            report($e);
            $mikrotik->disconnect();
            return redirect()->route('ppp.secrets')->with('error', 'Gagal memperbarui PPP Secret: ' . $e->getMessage());
        }
        $mikrotik->disconnect();

        return redirect()->route('ppp.secrets')->with('success', 'PPP Secret berhasil diperbarui.');
    }

    /** Hapus PPP Secret */
    public function destroySecret(Request $request, string $id): RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $mikrotik = $ctx['mikrotik'];

        try {
            $mikrotik->comm('/ppp/secret/remove', ['.id' => $id]);
        } catch (\Throwable $e) {
            report($e);
            $mikrotik->disconnect();
            return redirect()->route('ppp.secrets')->with('error', 'Gagal menghapus PPP Secret: ' . $e->getMessage());
        }
        $mikrotik->disconnect();

        return redirect()->route('ppp.secrets')->with('success', 'PPP Secret berhasil dihapus.');
    }

    /** PPP Profiles list */
    public function profiles(Request $request): View|RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $mikrotik = $ctx['mikrotik'];

        $profiles = [];
        try {
            $profiles = $mikrotik->comm('/ppp/profile/print');
            $profiles = is_array($profiles) ? $profiles : [];
        } catch (\Throwable $e) {
            report($e);
        }
        $mikrotik->disconnect();

        return view('ppp.profiles', array_merge($ctx, ['profiles' => $profiles]));
    }

    /** Simpan PPP Profile */
    public function storeProfile(Request $request): RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $mikrotik = $ctx['mikrotik'];

        $valid = $request->validate([
            'name' => 'required|string|max:255',
            'local-address' => 'nullable|string|max:255',
            'remote-address' => 'nullable|string|max:255',
            'rate-limit' => 'nullable|string|max:255',
            'only-one' => 'nullable|string|in:yes,no,default',
            'address-list' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:255',
        ]);

        $params = [
            'name' => $valid['name'],
        ];
        foreach (['local-address', 'remote-address', 'rate-limit', 'only-one', 'address-list', 'comment'] as $key) {
            if ($request->filled($key)) {
                $params[$key] = (string) $request->input($key);
            }
        }

        try {
            $mikrotik->comm('/ppp/profile/add', $params);
        } catch (\Throwable $e) {
            report($e);
            $mikrotik->disconnect();
            return redirect()->route('ppp.profiles')->with('error', 'Gagal menambah PPP Profile: ' . $e->getMessage());
        }
        $mikrotik->disconnect();

        return redirect()->route('ppp.profiles')->with('success', 'PPP Profile berhasil ditambah.');
    }

    /** PPP Active (koneksi aktif) */
    public function active(Request $request): View|RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $mikrotik = $ctx['mikrotik'];

        $list = [];
        try {
            $list = $mikrotik->comm('/ppp/active/print');
            $list = is_array($list) ? $list : [];
        } catch (\Throwable $e) {
            report($e);
        }
        $mikrotik->disconnect();

        return view('ppp.active', array_merge($ctx, ['actives' => $list]));
    }

    /** Tagihan WiFi / Billing: daftar pelanggan = PPP Secrets (router) + data billing dari DB (sesuai template) */
    public function billing(Request $request): View|RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $view = $request->get('view', 'customers');
        $sessionName = $request->session()->get('selected_router');
        $mikrotik = $ctx['mikrotik'];

        // Data pelanggan = dari PPP Secrets (router), seperti template
        $pppSecrets = [];
        $onlineUsers = [];
        try {
            $pppSecrets = $mikrotik->comm('/ppp/secret/print');
            $pppSecrets = is_array($pppSecrets) ? $pppSecrets : [];
            $pppActive = $mikrotik->comm('/ppp/active/print');
            $pppActive = is_array($pppActive) ? $pppActive : [];
            foreach ($pppActive as $a) {
                if (! empty($a['name'])) {
                    $onlineUsers[$a['name']] = true;
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
        $mikrotik->disconnect();

        // Data billing dari DB (due_day, phone, display_name, monthly_fee) — digabung per customer_name
        $billingQuery = BillingCustomer::orderBy('customer_name');
        if ($sessionName) {
            $billingQuery->where(function ($q) use ($sessionName) {
                $q->where('router_session', $sessionName)->orWhereNull('router_session');
            });
        }
        $billingByCustomer = $billingQuery->get()->keyBy('customer_name');

        $billingPeriod = now()->format('Y-m');
        $promisesByCustomer = BillingPeriodPromise::query()
            ->where('period', $billingPeriod)
            ->where('router_session', (string) $sessionName)
            ->get()
            ->keyBy('customer_name');

        $customersWithStatus = [];
        $countOverdue = $countDueToday = $countDueSoon = $countPaid = $countNotSet = 0;
        $countArrearsPast = 0;
        $statusPriority = ['overdue' => 1, 'due_today' => 2, 'due_soon' => 3, 'waiting' => 4, 'paid' => 5, 'not_set' => 6];

        foreach ($pppSecrets as $secret) {
            $customerName = $secret['name'] ?? null;
            if (! $customerName) {
                continue;
            }
            $billing = $billingByCustomer->get($customerName);
            $dueDay = $billing ? (int) $billing->due_day : 0;
            $phone = $billing?->phone ?? '';
            $displayName = $billing?->display_name ?? '';
            $monthlyFee = $billing ? (int) $billing->monthly_fee : 0;
            $notes = $billing?->notes ?? '';
            $address = $billing?->address ?? '';
            $zone = $billing?->zone ?? '';
            $packageLabel = $billing?->package_label ?? '';
            $email = $billing?->email ?? '';
            $startedAt = $billing?->started_at;

            $promiseRow = $promisesByCustomer->get($customerName);
            $promisedPayDate = $promiseRow?->promised_pay_date?->format('Y-m-d');
            $promisedPayNote = $promiseRow?->promised_pay_note ?? '';
            $status = BillingStatusHelper::getStatus($customerName, $dueDay, $promisedPayDate);
            $arrears = BillingArrearsHelper::getUnpaidPeriodsSummary($customerName, $dueDay, $monthlyFee, (string) $sessionName);
            if (($arrears['unpaid_past_count'] ?? 0) > 0) {
                $countArrearsPast++;
            }

            $arrearsBadgeClass = 'arrears-ok';
            if (($arrears['unpaid_past_count'] ?? 0) > 0) {
                $arrearsBadgeClass = 'arrears-past';
            } elseif (($arrears['unpaid_count'] ?? 0) > 0) {
                $arrearsBadgeClass = 'arrears-current';
            }

            $row = (object) [
                'id' => $billing?->id,
                'customer_name' => $customerName,
                'display_name' => $displayName,
                'phone' => $phone,
                'monthly_fee' => $monthlyFee,
                'due_day' => $dueDay,
                'notes' => $notes,
                'address' => $address,
                'zone' => $zone,
                'package_label' => $packageLabel,
                'email' => $email,
                'started_at' => $startedAt,
                'profile' => $secret['profile'] ?? '',
                'comment' => $secret['comment'] ?? '',
                'status' => $status['status'],
                'status_label' => $status['status_label'],
                'status_class' => $status['status_class'],
                'days_to_due' => $status['days_to_due'],
                'current_period' => $status['current_period'],
                'due_date' => $status['original_due_date'] ?? $status['due_date'],
                'effective_due_date' => $status['due_date'] ?? null,
                'original_due_date' => $status['original_due_date'] ?? null,
                'is_promised' => (bool) ($status['is_promised'] ?? false),
                'promised_pay_date' => $status['promised_pay_date'] ?? $promisedPayDate,
                'promised_pay_note' => $promisedPayNote,
                'message' => $status['message'],
                'online' => ! empty($onlineUsers[$customerName]),
                'arrears_label' => $arrears['label'],
                'arrears_tooltip' => $arrears['tooltip'],
                'outstanding_total' => (int) ($arrears['outstanding_total'] ?? 0),
                'unpaid_periods_count' => $arrears['unpaid_count'],
                'unpaid_past_count' => $arrears['unpaid_past_count'],
                'arrears_badge_class' => $arrearsBadgeClass,
            ];
            $customersWithStatus[] = $row;
            if ($status['status'] === 'overdue') $countOverdue++;
            elseif ($status['status'] === 'due_today') $countDueToday++;
            elseif ($status['status'] === 'due_soon') $countDueSoon++;
            elseif ($status['status'] === 'paid') $countPaid++;
            else $countNotSet++;
        }

        usort($customersWithStatus, function ($a, $b) use ($statusPriority) {
            $pastA = (int) ($a->unpaid_past_count ?? 0);
            $pastB = (int) ($b->unpaid_past_count ?? 0);
            if ($pastA !== $pastB) {
                return $pastB <=> $pastA;
            }
            $totA = (int) ($a->unpaid_periods_count ?? 0);
            $totB = (int) ($b->unpaid_periods_count ?? 0);
            if ($totA !== $totB) {
                return $totB <=> $totA;
            }
            $pA = $statusPriority[$a->status] ?? 99;
            $pB = $statusPriority[$b->status] ?? 99;
            if ($pA !== $pB) {
                return $pA - $pB;
            }
            $dA = $a->days_to_due;
            $dB = $b->days_to_due;
            if ($dA === null && $dB === null) {
                return 0;
            }
            if ($dA === null) {
                return 1;
            }
            if ($dB === null) {
                return -1;
            }

            return $dA - $dB;
        });

        $payments = BillingPayment::where('router_session', $sessionName)
            ->orderByDesc('payment_date')
            ->limit(100)
            ->get();
        $reportYear = (int) $request->get('year', date('Y'));
        $reportMonth = (int) $request->get('month', date('n'));
        if ($reportMonth < 1 || $reportMonth > 12) {
            $reportMonth = (int) date('n');
        }

        $monthlyRowsRaw = BillingPayment::query()
            ->selectRaw('YEAR(payment_date) as y, MONTH(payment_date) as m')
            ->selectRaw('SUM(amount) as total_amount')
            ->selectRaw('COUNT(*) as total_transactions')
            ->selectRaw("SUM(CASE WHEN LOWER(COALESCE(payment_method, '')) = 'tunai' THEN amount ELSE 0 END) as total_cash")
            ->selectRaw("SUM(CASE WHEN LOWER(COALESCE(payment_method, '')) IN ('transfer', 'bank') THEN amount ELSE 0 END) as total_transfer")
            ->where('router_session', $sessionName)
            ->whereIn('status', ['paid', 'partial'])
            ->whereNotNull('payment_date')
            ->groupBy(DB::raw('YEAR(payment_date)'), DB::raw('MONTH(payment_date)'))
            ->orderByDesc(DB::raw('YEAR(payment_date)'))
            ->orderByDesc(DB::raw('MONTH(payment_date)'))
            ->get();

        $availableReportYears = $monthlyRowsRaw
            ->pluck('y')
            ->map(fn ($y) => (int) $y)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
        if (empty($availableReportYears)) {
            $availableReportYears = [(int) date('Y')];
        }
        if (! in_array($reportYear, $availableReportYears, true)) {
            $reportYear = (int) $availableReportYears[0];
        }

        $summaryByMonth = $monthlyRowsRaw
            ->filter(fn ($row) => (int) $row->y === $reportYear)
            ->map(function ($row) {
                $transactions = (int) ($row->total_transactions ?? 0);
                $totalAmount = (int) ($row->total_amount ?? 0);

                return (object) [
                    'year' => (int) $row->y,
                    'month' => (int) $row->m,
                    'period' => sprintf('%04d-%02d', (int) $row->y, (int) $row->m),
                    'total_amount' => $totalAmount,
                    'total_transactions' => $transactions,
                    'avg_amount' => $transactions > 0 ? (int) floor($totalAmount / $transactions) : 0,
                    'total_cash' => (int) ($row->total_cash ?? 0),
                    'total_transfer' => (int) ($row->total_transfer ?? 0),
                ];
            })
            ->values();

        $reportYearTotal = (int) $summaryByMonth->sum('total_amount');
        $reportYearTransactions = (int) $summaryByMonth->sum('total_transactions');
        $reportPayments = BillingPayment::whereIn('status', ['paid', 'partial'])
            ->where('router_session', $sessionName)
            ->whereYear('payment_date', $reportYear)
            ->whereMonth('payment_date', $reportMonth)
            ->orderByDesc('payment_date')
            ->get();
        $reportTotal = $reportPayments->sum('amount');

        // Daftar pelanggan untuk modal Catat Pembayaran (dropdown)
        $paymentCustomerList = [];
        foreach ($pppSecrets as $secret) {
            $name = $secret['name'] ?? null;
            if (! $name) {
                continue;
            }
            $billing = $billingByCustomer->get($name);
            $paymentCustomerList[] = (object) [
                'customer_name' => $name,
                'display_name' => $billing?->display_name ?? '',
            ];
        }
        usort($paymentCustomerList, fn ($a, $b) => strcasecmp($a->customer_name, $b->customer_name));

        return view('ppp.billing', array_merge($ctx, [
            'view' => $view,
            'billingPeriod' => $billingPeriod,
            'billingPeriodLabel' => Carbon::parse($billingPeriod . '-01')->translatedFormat('F Y'),
            'customers' => $customersWithStatus,
            'paymentCustomerList' => $paymentCustomerList,
            'payments' => $payments,
            'countOverdue' => $countOverdue,
            'countDueToday' => $countDueToday,
            'countDueSoon' => $countDueSoon,
            'countPaid' => $countPaid,
            'countNotSet' => $countNotSet,
            'countArrearsPast' => $countArrearsPast,
            'billingTrackingConfigured' => BillingArrearsHelper::hasExplicitTrackingStart(),
            'reportPayments' => $reportPayments,
            'reportTotal' => $reportTotal,
            'reportYear' => $reportYear,
            'reportMonth' => $reportMonth,
            'summaryByMonth' => $summaryByMonth,
            'reportYearTotal' => $reportYearTotal,
            'reportYearTransactions' => $reportYearTransactions,
            'availableReportYears' => $availableReportYears,
        ]));
    }

    /** Export laporan rekap bulanan ke PDF dengan kop usaha */
    public function exportBillingReportPdf(Request $request): RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }

        $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $reportYear = (int) $request->get('year', date('Y'));
        if ($reportYear < 2000 || $reportYear > 2100) {
            $reportYear = (int) date('Y');
        }

        $summaryByMonth = BillingPayment::query()
            ->selectRaw('YEAR(payment_date) as y, MONTH(payment_date) as m')
            ->selectRaw('SUM(amount) as total_amount')
            ->selectRaw('COUNT(*) as total_transactions')
            ->selectRaw("SUM(CASE WHEN LOWER(COALESCE(payment_method, '')) = 'tunai' THEN amount ELSE 0 END) as total_cash")
            ->selectRaw("SUM(CASE WHEN LOWER(COALESCE(payment_method, '')) IN ('transfer', 'bank') THEN amount ELSE 0 END) as total_transfer")
            ->where('router_session', (string) $request->session()->get('selected_router'))
            ->whereIn('status', ['paid', 'partial'])
            ->whereNotNull('payment_date')
            ->whereYear('payment_date', $reportYear)
            ->groupBy(DB::raw('YEAR(payment_date)'), DB::raw('MONTH(payment_date)'))
            ->orderBy(DB::raw('MONTH(payment_date)'))
            ->get()
            ->map(function ($row) {
                $transactions = (int) ($row->total_transactions ?? 0);
                $totalAmount = (int) ($row->total_amount ?? 0);
                return (object) [
                    'year' => (int) $row->y,
                    'month' => (int) $row->m,
                    'period' => sprintf('%04d-%02d', (int) $row->y, (int) $row->m),
                    'total_amount' => $totalAmount,
                    'total_transactions' => $transactions,
                    'avg_amount' => $transactions > 0 ? (int) floor($totalAmount / $transactions) : 0,
                    'total_cash' => (int) ($row->total_cash ?? 0),
                    'total_transfer' => (int) ($row->total_transfer ?? 0),
                ];
            })
            ->values();

        $settings = BusinessSetting::get();
        $reportYearTotal = (int) $summaryByMonth->sum('total_amount');
        $reportYearTransactions = (int) $summaryByMonth->sum('total_transactions');

        $pdf = Pdf::loadView('ppp.billing-report-pdf', [
            'business' => $settings,
            'reportYear' => $reportYear,
            'summaryByMonth' => $summaryByMonth,
            'reportYearTotal' => $reportYearTotal,
            'reportYearTransactions' => $reportYearTransactions,
            'months' => $months,
            'printedAt' => now(),
            'letterheadDataUri' => $this->resolveLetterheadDataUri($settings),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('laporan-pembayaran-bulanan-' . $reportYear . '.pdf');
    }

    /** Form tambah / atur pelanggan billing (optional ?name= untuk pre-fill dari daftar PPP) */
    public function createCustomer(Request $request): View|RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $name = $request->get('name');
        $sessionName = $request->session()->get('selected_router');
        $customer = null;
        if ($name) {
            $q = BillingCustomer::where('customer_name', $name);
            if ($sessionName) {
                $q->where(function ($q2) use ($sessionName) {
                    $q2->where('router_session', $sessionName)->orWhereNull('router_session');
                });
            }
            $customer = $q->first();
        }
        $promisePeriod = now()->format('Y-m');
        $periodPromise = null;
        $cn = $name ?? $customer?->customer_name;
        if ($cn && $sessionName) {
            $periodPromise = BillingPeriodPromise::query()
                ->where('customer_name', $cn)
                ->where('period', $promisePeriod)
                ->where('router_session', (string) $sessionName)
                ->first();
        }

        return view('ppp.billing-customer-form', array_merge($ctx, [
            'customer' => $customer,
            'customer_name' => $name ?? $customer?->customer_name,
            'promisePeriod' => $promisePeriod,
            'promisePeriodLabel' => Carbon::parse($promisePeriod . '-01')->translatedFormat('F Y'),
            'periodPromise' => $periodPromise,
        ]));
    }

    /** Simpan / update pelanggan billing (by customer_name, sesuai template) */
    public function storeCustomer(Request $request): RedirectResponse|JsonResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Sesi router tidak valid. Muat ulang halaman atau pilih router lagi.',
                ], 403);
            }

            return $ctx;
        }
        $valid = $request->validate([
            'customer_name' => 'required|string|max:255',
            'due_day' => 'required|integer|min:0|max:31',
            'display_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'monthly_fee' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:500',
            'address' => 'nullable|string|max:2000',
            'zone' => 'nullable|string|max:100',
            'package_label' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'started_at' => 'nullable|date',
        ]);
        $valid['router_session'] = $request->session()->get('selected_router');
        $valid['monthly_fee'] = (int) ($valid['monthly_fee'] ?? 0);
        $valid['started_at'] = $request->filled('started_at') ? $request->date('started_at') : null;
        foreach (['display_name', 'phone', 'notes', 'address', 'zone', 'package_label', 'email'] as $nullableString) {
            if (array_key_exists($nullableString, $valid) && $valid[$nullableString] === '') {
                $valid[$nullableString] = null;
            }
        }
        BillingCustomer::updateOrCreate(
            ['customer_name' => $valid['customer_name']],
            $valid
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data pelanggan berhasil disimpan.',
            ]);
        }

        return redirect()->route('ppp.billing', ['view' => 'customers'])->with('success', 'Data pelanggan berhasil disimpan.');
    }

    /**
     * Janji bayar hanya untuk satu periode tagihan (YYYY-MM). Bulan berikutnya tidak otomatis punya janji.
     */
    public function storePeriodPromise(Request $request): JsonResponse|RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => 'Sesi router tidak valid.'], 403);
            }

            return $ctx;
        }

        $sessionName = (string) $request->session()->get('selected_router');
        $currentPeriod = now()->format('Y-m');

        $valid = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'period' => ['required', 'string', 'size:7', 'regex:/^\d{4}-\d{2}$/'],
            'promised_pay_date' => ['nullable', 'date'],
            'promised_pay_note' => ['nullable', 'string', 'max:255'],
            'clear_promise' => ['nullable', 'boolean'],
        ]);

        if ($valid['period'] !== $currentPeriod) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => 'Periode janji harus bulan berjalan (' . $currentPeriod . ').'], 422);
            }

            return redirect()->route('ppp.billing', ['view' => 'customers'])
                ->with('error', 'Periode janji tidak valid.');
        }

        $note = trim((string) ($valid['promised_pay_note'] ?? ''));
        $note = $note !== '' ? $note : null;

        if ($request->boolean('clear_promise')) {
            BillingPeriodPromise::query()
                ->where('customer_name', $valid['customer_name'])
                ->where('router_session', $sessionName)
                ->where('period', $currentPeriod)
                ->delete();
            $msg = 'Janji bayar untuk periode ini dihapus.';
        } elseif ($request->filled('promised_pay_date')) {
            BillingPeriodPromise::updateOrCreate(
                [
                    'customer_name' => $valid['customer_name'],
                    'router_session' => $sessionName,
                    'period' => $currentPeriod,
                ],
                [
                    'promised_pay_date' => $request->date('promised_pay_date'),
                    'promised_pay_note' => $note,
                ]
            );
            $msg = 'Janji bayar untuk periode ' . $currentPeriod . ' disimpan.';
        } else {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Isi tanggal janji bayar, atau gunakan tombol Hapus janji.',
                    'errors' => ['promised_pay_date' => ['Wajib isi tanggal atau hapus janji.']],
                ], 422);
            }

            return redirect()->route('ppp.billing', ['view' => 'customers'])
                ->with('error', 'Isi tanggal janji bayar atau hapus janji.');
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return redirect()->route('ppp.billing', ['view' => 'customers'])->with('success', $msg);
    }

    /** Form edit/atur billing pelanggan (by customer_name; bisa PPP user yang belum punya record) */
    public function editCustomer(Request $request): View|RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $name = $request->get('name');
        $sessionName = $request->session()->get('selected_router');
        $customer = null;
        if ($name) {
            $q = BillingCustomer::where('customer_name', $name);
            if ($sessionName) {
                $q->where(function ($q2) use ($sessionName) {
                    $q2->where('router_session', $sessionName)->orWhereNull('router_session');
                });
            }
            $customer = $q->first();
        }
        $promisePeriod = now()->format('Y-m');
        $periodPromise = null;
        $cn = $name ?? $customer?->customer_name;
        if ($cn && $sessionName) {
            $periodPromise = BillingPeriodPromise::query()
                ->where('customer_name', $cn)
                ->where('period', $promisePeriod)
                ->where('router_session', (string) $sessionName)
                ->first();
        }

        return view('ppp.billing-customer-form', array_merge($ctx, [
            'customer' => $customer,
            'customer_name' => $name ?? $customer?->customer_name,
            'promisePeriod' => $promisePeriod,
            'promisePeriodLabel' => Carbon::parse($promisePeriod . '-01')->translatedFormat('F Y'),
            'periodPromise' => $periodPromise,
        ]));
    }

    /** Simpan pembayaran (redirect biasa atau JSON untuk AJAX/modal) */
    public function storePayment(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $valid = $request->validate([
            'customer_name' => 'required|string|max:255',
            'amount' => 'required|integer|min:1',
            'period' => 'required|string|size:7|regex:/^\d{4}-\d{2}$/',
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
            'catat_ke_kas' => 'nullable|boolean',
        ]);
        $catatKeKas = $request->boolean('catat_ke_kas', true);

        $sessionName = (string) $request->session()->get('selected_router');
        $customer = BillingCustomer::where('customer_name', $valid['customer_name'])
            ->where(function ($q) use ($sessionName) {
                $q->where('router_session', $sessionName)->orWhereNull('router_session');
            })
            ->first();
        $periodTotalPaid = BillingPayment::query()
            ->where('customer_name', $valid['customer_name'])
            ->where('period', $valid['period'])
            ->where('router_session', $sessionName)
            ->sum('amount');

        $monthlyFee = (int) ($customer?->monthly_fee ?? 0);
        $currentPaid = $periodTotalPaid + (int) $valid['amount'];
        $paymentStatus = ($monthlyFee > 0 && $currentPaid < $monthlyFee) ? 'partial' : 'paid';
        $remainingShortfall = max(0, $monthlyFee - $currentPaid);

        $notes = trim((string) ($valid['notes'] ?? ''));
        if ($remainingShortfall > 0) {
            $carryNote = 'Sisa tagihan periode ini: Rp ' . number_format($remainingShortfall, 0, ',', '.') . ' (carry forward)';
            $notes = $notes !== '' ? ($notes . ' | ' . $carryNote) : $carryNote;
        }

        $payment = BillingPayment::create([
            'customer_name' => $valid['customer_name'],
            'display_name' => $customer?->display_name,
            'phone' => $customer?->phone,
            'amount' => (int) $valid['amount'],
            'remaining_amount' => $remainingShortfall,
            'period' => $valid['period'],
            'payment_date' => $valid['payment_date'] . ' ' . date('H:i:s'),
            'payment_method' => $valid['payment_method'] ?? 'Transfer',
            'notes' => $notes !== '' ? $notes : null,
            'status' => $paymentStatus,
            'router_session' => $sessionName,
        ]);

        if ($paymentStatus === 'paid') {
            BillingPeriodPromise::query()
                ->where('customer_name', $valid['customer_name'])
                ->where('router_session', $sessionName)
                ->where('period', $valid['period'])
                ->delete();
        }
        if (empty($payment->invoice_id)) {
            $payment->invoice_id = $this->formatInvoiceId($payment->period, (int) $payment->id);
            $payment->save();
        }

        if ($catatKeKas && ! CashTransaction::where('source_type', 'ppp_payment')->where('source_id', $payment->id)->exists()) {
            $metode = $this->mapPaymentMethodToCashMetode($valid['payment_method'] ?? 'Transfer');
            $keterangan = 'Pembayaran ' . ($customer?->display_name ?: $valid['customer_name']) . ' — ' . ($payment->invoice_id ?? 'INV-' . $payment->id);
            CashTransaction::create([
                'tanggal' => $valid['payment_date'],
                'tipe' => 'masuk',
                'kategori' => 'ppp',
                'nominal' => (int) $valid['amount'],
                'metode' => $metode,
                'keterangan' => $keterangan,
                'router_session' => $sessionName,
                'source_type' => 'ppp_payment',
                'source_id' => $payment->id,
            ]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $remainingShortfall > 0
                    ? ('Pembayaran parsial dicatat. Sisa tagihan: Rp ' . number_format($remainingShortfall, 0, ',', '.'))
                    : 'Pembayaran berhasil dicatat.',
                'invoice_id' => $payment->invoice_id,
                'payment_id' => $payment->id,
                'payment_status' => $paymentStatus,
                'remaining_shortfall' => $remainingShortfall,
            ]);
        }
        $flashMessage = $remainingShortfall > 0
            ? ('Pembayaran parsial dicatat. Sisa tagihan: Rp ' . number_format($remainingShortfall, 0, ',', '.') . ' (otomatis dibawa ke periode berikutnya).')
            : 'Pembayaran berhasil dicatat.';

        return redirect()->route('ppp.billing', ['view' => 'invoices'])->with('success', $flashMessage);
    }

    /** Hapus pembayaran + hapus kas auto-sync terkait (jika ada) */
    public function destroyPayment(Request $request, BillingPayment $payment): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $sessionName = (string) $request->session()->get('selected_router');
        if ($sessionName === '') {
            return redirect()->route('sessions');
        }
        if ((string) ($payment->router_session ?? '') !== $sessionName) {
            abort(403, 'Akses ditolak.');
        }

        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }

        CashTransaction::where('source_type', 'ppp_payment')
            ->where('source_id', $payment->id)
            ->where('router_session', $sessionName)
            ->delete();

        $invoiceId = $payment->invoice_id ?: ('INV-' . str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT));
        $payment->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Riwayat pembayaran ' . $invoiceId . ' berhasil dihapus.',
            ]);
        }

        return redirect()->route('ppp.billing', ['view' => 'invoices'])
            ->with('success', 'Riwayat pembayaran ' . $invoiceId . ' berhasil dihapus.');
    }

    /** Cetak invoice pembayaran (Laravel view) */
    public function printInvoice(Request $request, BillingPayment $payment): View|RedirectResponse
    {
        $sessionName = (string) $request->session()->get('selected_router');
        if ($sessionName === '') {
            return redirect()->route('sessions');
        }
        if ((string) ($payment->router_session ?? '') !== $sessionName) {
            abort(403, 'Akses ditolak.');
        }

        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }

        if (empty($payment->invoice_id)) {
            $payment->invoice_id = $this->formatInvoiceId($payment->period, (int) $payment->id);
            $payment->save();
        }

        return view('ppp.billing-invoice-print', array_merge($ctx, [
            'payment' => $payment,
        ]));
    }

    /**
     * Unduh template XLSX formal: baris pelanggan PPP dengan status tagihan "Belum Diatur" (tanggal jatuh tempo belum diisi).
     */
    public function exportBillingTemplate(Request $request, BillingCustomerTemplateService $templateService): RedirectResponse|StreamedResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $mikrotik = $ctx['mikrotik'];
        $sessionName = $request->session()->get('selected_router');
        $pppSecrets = [];
        try {
            $pppSecrets = $mikrotik->comm('/ppp/secret/print');
            $pppSecrets = is_array($pppSecrets) ? $pppSecrets : [];
        } catch (\Throwable $e) {
            report($e);
        }
        $mikrotik->disconnect();

        $billingQuery = BillingCustomer::orderBy('customer_name');
        if ($sessionName) {
            $billingQuery->where(function ($q) use ($sessionName) {
                $q->where('router_session', $sessionName)->orWhereNull('router_session');
            });
        }
        $billingByCustomer = $billingQuery->get()->keyBy('customer_name');

        $spreadsheet = $templateService->buildExportNotSet($pppSecrets, $billingByCustomer);

        return $templateService->streamXlsxDownload(
            $spreadsheet,
            'MIKPAY-template-tagihan-wifi.xlsx'
        );
    }

    /**
     * Impor baris dari file XLSX (template yang sama). Username harus cocok dengan PPP Secret router.
     */
    public function importBillingTemplate(Request $request, BillingCustomerTemplateService $templateService): RedirectResponse
    {
        $ctx = $this->ensureRouter($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $request->validate([
            'file' => 'required|file|mimes:xlsx|max:10240',
        ], [
            'file.mimes' => 'Gunakan file .xlsx (Excel).',
        ]);

        $mikrotik = $ctx['mikrotik'];
        $pppSecrets = [];
        try {
            $pppSecrets = $mikrotik->comm('/ppp/secret/print');
            $pppSecrets = is_array($pppSecrets) ? $pppSecrets : [];
        } catch (\Throwable $e) {
            report($e);
        }
        $mikrotik->disconnect();

        $allowed = [];
        foreach ($pppSecrets as $s) {
            $n = $s['name'] ?? '';
            if ($n !== '') {
                $allowed[$n] = true;
            }
        }

        $path = $request->file('file')->getRealPath();
        if (! $path || ! is_readable($path)) {
            return redirect()->route('ppp.billing', ['view' => 'customers'])
                ->with('error', 'File tidak dapat dibaca.');
        }

        $sessionName = $request->session()->get('selected_router');
        $result = $templateService->importFromPath($path, $allowed, $sessionName);

        if ($result['saved'] === 0 && count($result['errors']) === 0) {
            return redirect()->route('ppp.billing', ['view' => 'customers'])
                ->with('error', 'Tidak ada baris data yang diisi. Isi kolom Username PPP dan Tanggal jatuh tempo (1–31).');
        }

        if ($result['saved'] === 0 && count($result['errors']) > 0) {
            return redirect()->route('ppp.billing', ['view' => 'customers'])
                ->with('error', 'Import gagal: ' . implode(' ', array_slice($result['errors'], 0, 12)));
        }

        $msg = 'Import berhasil: ' . $result['saved'] . ' pelanggan diperbarui.';
        if ($result['skipped'] > 0) {
            $msg .= ' (' . $result['skipped'] . ' baris kosong dilewati.)';
        }
        $redirect = redirect()->route('ppp.billing', ['view' => 'customers'])->with('success', $msg);
        if (count($result['errors']) > 0) {
            $errText = implode(' ', array_slice($result['errors'], 0, 8));
            if (count($result['errors']) > 8) {
                $errText .= ' (+' . (count($result['errors']) - 8) . ' lainnya)';
            }

            return $redirect->with('error', 'Peringatan: ' . $errText);
        }

        return $redirect;
    }
}
