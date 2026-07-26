<?php

namespace App\Http\Controllers;

use App\Models\BillingPayment;
use App\Models\BusinessSetting;
use App\Models\CashTransaction;
use App\Models\MikpayRouter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class FinanceController extends Controller
{
    private function currentUserId(): int
    {
        return (int) (auth()->id() ?? 0);
    }

    private function selectedRouterSession(array $ctx): string
    {
        return (string) ($ctx['currentRouter']->session_name ?? '');
    }

    private function assertCashflowOwnership(CashTransaction $cashflow, string $routerSession): void
    {
        if ((string) ($cashflow->router_session ?? '') !== $routerSession) {
            abort(403, 'Akses ditolak.');
        }
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

    /**
     * Context layout: currentRouter, routers, identity, clock (tanpa koneksi Mikrotik).
     */
    private function ensureContext(Request $request): array|RedirectResponse
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
        $routers = MikpayRouter::where('user_id', $userId)->where('status', 'enable')->orderBy('session_name')->get();

        return [
            'currentRouter' => $router,
            'routers' => $routers,
            'identity' => $router->router_name ?: $sessionName,
            'clock' => ['time' => date('H:i:s'), 'date' => date('d M Y'), 'time-zone-name' => date_default_timezone_get()],
        ];
    }

    /** Kas Masuk/Keluar – list dengan filter */
    public function cashflow(Request $request): View|RedirectResponse
    {
        $ctx = $this->ensureContext($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $routerSession = $this->selectedRouterSession($ctx);

        $query = CashTransaction::query()->where('router_session', $routerSession);
        if ($request->filled('tanggal_awal')) {
            $query->where('tanggal', '>=', $request->tanggal_awal);
        }
        if ($request->filled('tanggal_akhir')) {
            $query->where('tanggal', '<=', $request->tanggal_akhir);
        }
        if ($request->filled('tipe')) {
            $query->where('tipe', $request->tipe);
        }
        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        $totalMasuk = (clone $query)->where('tipe', 'masuk')->sum('nominal');
        $totalKeluar = (clone $query)->where('tipe', 'keluar')->sum('nominal');
        $saldoPeriod = $totalMasuk - $totalKeluar;

        $items = $query
            ->with(['billingPayment:id,customer_name,display_name'])
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(50)
            ->withQueryString();

        $currency = $ctx['currentRouter']->currency ?: 'Rp';

        return view('keuangan.cashflow', array_merge($ctx, [
            'items' => $items,
            'totalMasuk' => $totalMasuk,
            'totalKeluar' => $totalKeluar,
            'saldoPeriod' => $saldoPeriod,
            'currency' => $currency,
        ]));
    }

    /** Simpan transaksi kas baru */
    public function storeCashflow(Request $request): RedirectResponse
    {
        $ctx = $this->ensureContext($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }

        $valid = $request->validate([
            'tanggal' => 'required|date',
            'tipe' => 'required|in:masuk,keluar',
            'kategori' => 'required|string|max:50',
            'nominal' => 'required|integer|min:1',
            'metode' => 'nullable|string|max:30',
            'keterangan' => 'nullable|string|max:500',
        ]);

        CashTransaction::create([
            'tanggal' => $valid['tanggal'],
            'tipe' => $valid['tipe'],
            'kategori' => $valid['kategori'],
            'nominal' => (int) $valid['nominal'],
            'metode' => $valid['metode'] ?? 'tunai',
            'keterangan' => $valid['keterangan'] ?? null,
            'router_session' => $this->selectedRouterSession($ctx),
        ]);

        return redirect()->route('keuangan.cashflow')->with('success', 'Transaksi berhasil ditambahkan.');
    }

    /** Form edit transaksi kas */
    public function editCashflow(Request $request, CashTransaction $cashflow): View|RedirectResponse
    {
        $ctx = $this->ensureContext($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $this->assertCashflowOwnership($cashflow, $this->selectedRouterSession($ctx));

        return view('keuangan.cashflow-edit', array_merge($ctx, ['cashflow' => $cashflow]));
    }

    /** Update transaksi kas */
    public function updateCashflow(Request $request, CashTransaction $cashflow): RedirectResponse
    {
        $ctx = $this->ensureContext($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $this->assertCashflowOwnership($cashflow, $this->selectedRouterSession($ctx));

        $valid = $request->validate([
            'tanggal' => 'required|date',
            'tipe' => 'required|in:masuk,keluar',
            'kategori' => 'required|string|max:50',
            'nominal' => 'required|integer|min:1',
            'metode' => 'nullable|string|max:30',
            'keterangan' => 'nullable|string|max:500',
        ]);

        $cashflow->update([
            'tanggal' => $valid['tanggal'],
            'tipe' => $valid['tipe'],
            'kategori' => $valid['kategori'],
            'nominal' => (int) $valid['nominal'],
            'metode' => $valid['metode'] ?? 'tunai',
            'keterangan' => $valid['keterangan'] ?? null,
        ]);

        return redirect()->route('keuangan.cashflow')->with('success', 'Transaksi berhasil diubah.');
    }

    /** Hapus transaksi kas */
    public function destroyCashflow(Request $request, CashTransaction $cashflow): RedirectResponse
    {
        $ctx = $this->ensureContext($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $this->assertCashflowOwnership($cashflow, $this->selectedRouterSession($ctx));

        $cashflow->delete();

        return redirect()->route('keuangan.cashflow')->with('success', 'Transaksi berhasil dihapus.');
    }

    /** Laporan Keuangan – pusat preview dan export laporan resmi */
    public function report(Request $request): View|RedirectResponse
    {
        $ctx = $this->ensureContext($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }
        $routerSession = $this->selectedRouterSession($ctx);

        $reportTab = (string) $request->get('tab', 'monthly');
        if (! in_array($reportTab, ['monthly', 'yearly'], true)) {
            $reportTab = 'monthly';
        }

        $reportYear = (int) $request->get('year', date('Y'));
        if ($reportYear < 2000 || $reportYear > 2100) {
            $reportYear = (int) date('Y');
        }
        $reportMonth = (int) $request->get('month', date('n'));
        if ($reportMonth < 1 || $reportMonth > 12) {
            $reportMonth = (int) date('n');
        }

        // Pendapatan PPP per bulan.
        $pppRows = BillingPayment::query()
            ->selectRaw('MONTH(payment_date) as m')
            ->selectRaw('SUM(amount) as total_amount')
            ->where('router_session', $routerSession)
            ->where('status', 'paid')
            ->whereNotNull('payment_date')
            ->whereYear('payment_date', $reportYear)
            ->groupBy(DB::raw('MONTH(payment_date)'))
            ->pluck('total_amount', 'm');

        // Kas non-PPP per bulan (hindari double-count pembayaran PPP yang sudah auto-sync ke kas).
        $cashRows = CashTransaction::query()
            ->selectRaw('MONTH(tanggal) as m')
            ->selectRaw("SUM(CASE WHEN tipe='masuk' AND COALESCE(kategori,'') <> 'ppp' THEN nominal ELSE 0 END) as kas_masuk_non_ppp")
            ->selectRaw("SUM(CASE WHEN tipe='keluar' THEN nominal ELSE 0 END) as kas_keluar")
            ->where('router_session', $routerSession)
            ->whereYear('tanggal', $reportYear)
            ->groupBy(DB::raw('MONTH(tanggal)'))
            ->get()
            ->keyBy(fn ($r) => (int) $r->m);

        $monthly = collect(range(1, 12))->map(function (int $month) use ($reportYear, $pppRows, $cashRows) {
            $pppIncome = (int) ($pppRows[$month] ?? 0);
            $cash = $cashRows->get($month);
            $kasMasukNonPpp = (int) ($cash->kas_masuk_non_ppp ?? 0);
            $kasKeluar = (int) ($cash->kas_keluar ?? 0);
            $totalMasuk = $pppIncome + $kasMasukNonPpp;
            $net = $totalMasuk - $kasKeluar;

            return (object) [
                'year' => $reportYear,
                'month' => $month,
                'ppp_income' => $pppIncome,
                'cash_in_non_ppp' => $kasMasukNonPpp,
                'cash_out' => $kasKeluar,
                'total_in' => $totalMasuk,
                'net' => $net,
            ];
        });

        $totalPppIncome = (int) $monthly->sum('ppp_income');
        $totalCashInNonPpp = (int) $monthly->sum('cash_in_non_ppp');
        $totalCashOut = (int) $monthly->sum('cash_out');
        $grandTotalIn = (int) $monthly->sum('total_in');
        $netBalance = (int) $monthly->sum('net');

        $availableYears = collect()
            ->merge(
                BillingPayment::query()
                    ->selectRaw('YEAR(payment_date) as y')
                    ->where('router_session', $routerSession)
                    ->whereNotNull('payment_date')
                    ->groupBy(DB::raw('YEAR(payment_date)'))
                    ->pluck('y')
            )
            ->merge(
                CashTransaction::query()
                    ->selectRaw('YEAR(tanggal) as y')
                    ->where('router_session', $routerSession)
                    ->groupBy(DB::raw('YEAR(tanggal)'))
                    ->pluck('y')
            )
            ->map(fn ($y) => (int) $y)
            ->filter(fn ($y) => $y > 0)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
        if (empty($availableYears)) {
            $availableYears = [(int) date('Y')];
        }

        // Ringkasan bulanan terpilih.
        $monthlySelected = $monthly->firstWhere('month', $reportMonth);
        if (! $monthlySelected) {
            $monthlySelected = (object) [
                'year' => $reportYear,
                'month' => $reportMonth,
                'ppp_income' => 0,
                'cash_in_non_ppp' => 0,
                'cash_out' => 0,
                'total_in' => 0,
                'net' => 0,
            ];
        }

        // Breakdown detail bulan terpilih.
        $monthlyCashflows = CashTransaction::query()
            ->where('router_session', $routerSession)
            ->whereYear('tanggal', $reportYear)
            ->whereMonth('tanggal', $reportMonth)
            ->with(['billingPayment:id,customer_name,display_name'])
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get([
                'id',
                'tanggal',
                'tipe',
                'kategori',
                'nominal',
                'metode',
                'keterangan',
                'source_type',
                'source_id',
            ]);

        return view('keuangan.report', array_merge($ctx, [
            'reportTab' => $reportTab,
            'reportYear' => $reportYear,
            'reportMonth' => $reportMonth,
            'monthly' => $monthly,
            'monthlySelected' => $monthlySelected,
            'availableYears' => $availableYears,
            'totalPppIncome' => $totalPppIncome,
            'totalCashInNonPpp' => $totalCashInNonPpp,
            'totalCashOut' => $totalCashOut,
            'grandTotalIn' => $grandTotalIn,
            'netBalance' => $netBalance,
            'monthlyCashflows' => $monthlyCashflows,
        ]));
    }

    /** Export PDF laporan keuangan tahunan (dengan kop usaha) */
    public function reportPdf(Request $request): Response|RedirectResponse
    {
        $ctx = $this->ensureContext($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }

        $reportView = $this->report($request);
        if (! $reportView instanceof View) {
            return $reportView;
        }

        $data = $reportView->getData();
        $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $business = BusinessSetting::get();

        $pdf = Pdf::loadView('keuangan.report-pdf', array_merge($data, [
            'business' => $business,
            'months' => $months,
            'printedAt' => now(),
            'letterheadDataUri' => $this->resolveLetterheadDataUri($business),
        ]))->setPaper('a4', 'portrait');

        $filename = (($data['reportTab'] ?? 'monthly') === 'yearly')
            ? ('laporan-keuangan-tahunan-' . ($data['reportYear'] ?? date('Y')) . '.pdf')
            : ('laporan-keuangan-bulanan-' . ($data['reportYear'] ?? date('Y')) . '-' . str_pad((string) ($data['reportMonth'] ?? date('n')), 2, '0', STR_PAD_LEFT) . '.pdf');

        return $pdf->download($filename);
    }
}
