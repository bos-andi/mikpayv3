<?php

namespace App\Http\Controllers;

use App\Models\BillingCustomer;
use App\Models\BillingPayment;
use App\Models\CashTransaction;
use App\Models\FinanceDebt;
use App\Models\FinanceSetting;
use App\Models\Router;
use App\Support\ActiveRouter;
use App\Support\BillingArrearsHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        [$section, $range, $year, $month] = $this->parseNav($request);
        $periodEnd = $range === 'yearly'
            ? Carbon::create($year, 12, 31)->endOfDay()
            : Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        $monthly = $this->buildMonthlySeries($router, $year);
        $selected = $monthly->firstWhere('month', $month) ?? $this->emptyMonth($year, $month);

        $billingIncome = $range === 'monthly'
            ? (int) $selected->billing_income
            : (int) $monthly->sum('billing_income');
        $cashInNonBilling = $range === 'monthly'
            ? (int) $selected->cash_in
            : (int) $monthly->sum('cash_in');
        $totalOut = $range === 'monthly'
            ? (int) $selected->cash_out
            : (int) $monthly->sum('cash_out');
        $totalIn = $billingIncome + $cashInNonBilling;
        $net = $totalIn - $totalOut;

        $outstanding = $this->estimateOutstanding($router);
        $availableYears = $this->availableYears($router);
        $settings = $this->settingsFor($router);

        $incomeByCategory = $this->incomeLinesForPeriod($router, $range, $year, $month);
        $expenseByCategory = $this->expenseLinesForPeriod($router, $range, $year, $month);
        $labaRugi = $this->buildLabaRugi($router, $range, $year, $month);

        $paymentsQuery = BillingPayment::query()
            ->where('router_id', $router->id)
            ->whereNotNull('payment_date')
            ->whereYear('payment_date', $year);
        if ($range === 'monthly') {
            $paymentsQuery->whereMonth('payment_date', $month);
        }

        $payments = (clone $paymentsQuery)
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        $methodBreakdown = (clone $paymentsQuery)
            ->selectRaw("COALESCE(NULLIF(TRIM(payment_method), ''), 'lainnya') as method")
            ->selectRaw('SUM(amount) as total')
            ->selectRaw('COUNT(*) as cnt')
            ->groupBy('method')
            ->orderByDesc('total')
            ->get();

        $paymentCount = (clone $paymentsQuery)->count();
        $avgPayment = $paymentCount > 0 ? (int) round($billingIncome / $paymentCount) : 0;

        $cashQuery = CashTransaction::query()
            ->where('router_id', $router->id)
            ->whereYear('tanggal', $year);
        if ($range === 'monthly') {
            $cashQuery->whereMonth('tanggal', $month);
        }

        $cashItems = (clone $cashQuery)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(40)
            ->withQueryString();

        $neraca = $this->buildNeraca($router, $settings, $periodEnd, $outstanding);
        $debts = FinanceDebt::query()
            ->where('router_id', $router->id)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $trend = $section === 'overview' ? $this->buildTrendSeries($router, 12) : [];

        return view('panel.finance', [
            'router' => $router,
            'section' => $section,
            'range' => $range,
            'tab' => $range, // backward-compat alias for exports/partials
            'year' => $year,
            'month' => $month,
            'period' => sprintf('%04d-%02d', $year, $month),
            'periodEnd' => $periodEnd,
            'periodLabel' => $range === 'yearly'
                ? (string) $year
                : Carbon::create($year, $month, 1)->translatedFormat('F Y'),
            'monthly' => $monthly,
            'selected' => $selected,
            'payments' => $payments,
            'methodBreakdown' => $methodBreakdown,
            'cashItems' => $cashItems,
            'billingIncome' => $billingIncome,
            'cashInNonBilling' => $cashInNonBilling,
            'totalIn' => $totalIn,
            'totalOut' => $totalOut,
            'net' => $net,
            'incomeByCategory' => $incomeByCategory,
            'expenseByCategory' => $expenseByCategory,
            'labaRugi' => $labaRugi,
            'paymentCount' => $paymentCount,
            'avgPayment' => $avgPayment,
            'outstanding' => $outstanding,
            'availableYears' => $availableYears,
            'trend' => $trend,
            'settings' => $settings,
            'neraca' => $neraca,
            'debts' => $debts,
            'categoriesIn' => CashTransaction::CATEGORIES_IN,
            'categoriesOut' => array_values(array_filter(
                CashTransaction::CATEGORIES_OUT,
                fn ($c) => ! CashTransaction::isCapex($c)
            )),
            'assetPurchaseKeys' => FinanceSetting::ASSET_PURCHASE_KEYS,
        ]);
    }

    public function storeCash(Request $request): RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        $tenant = current_tenant();
        if (! $tenant?->id) {
            return back()->with('error', 'Tenant tidak ditemukan.');
        }

        $valid = $request->validate([
            'tanggal' => ['required', 'date'],
            'tipe' => ['required', 'in:masuk,keluar'],
            'kategori' => ['required', 'string', 'max:50'],
            'nominal' => ['required', 'integer', 'min:1'],
            'metode' => ['nullable', 'string', 'max:30'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        $allowed = $valid['tipe'] === CashTransaction::TYPE_IN
            ? CashTransaction::CATEGORIES_IN
            : CashTransaction::CATEGORIES_OUT;

        if (! in_array($valid['kategori'], $allowed, true)) {
            return back()->withInput()->with('error', 'Kategori tidak valid untuk tipe transaksi ini.');
        }

        // Belanja aset harus lewat form khusus agar neraca ikut bertambah.
        if ($valid['tipe'] === CashTransaction::TYPE_OUT && CashTransaction::isCapex($valid['kategori'])) {
            return back()->withInput()->with('error', 'Pakai form “Input belanja aset” untuk belanja modal.');
        }

        CashTransaction::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'tanggal' => $valid['tanggal'],
            'tipe' => $valid['tipe'],
            'kategori' => $valid['kategori'],
            'nominal' => (int) $valid['nominal'],
            'metode' => $valid['metode'] ?? 'tunai',
            'keterangan' => $valid['keterangan'] ?? null,
        ]);

        return redirect()
            ->to($this->financeRedirectUrl($request, 'kas'))
            ->with('success', 'Transaksi kas berhasil ditambahkan.');
    }

    public function storeAsset(Request $request): RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        $tenant = current_tenant();
        if (! $tenant?->id) {
            return back()->with('error', 'Tenant tidak ditemukan.');
        }

        $valid = $request->validate([
            'tanggal' => ['required', 'date'],
            'nominal' => ['required', 'integer', 'min:1'],
            'asset_key' => ['required', 'string', 'in:'.implode(',', array_keys(FinanceSetting::ASSET_PURCHASE_KEYS))],
            'metode' => ['nullable', 'string', 'max:30'],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'pay_from_cash' => ['nullable', 'boolean'],
        ]);

        $assetKey = (string) $valid['asset_key'];
        $nominal = (int) $valid['nominal'];
        $payFromCash = $request->boolean('pay_from_cash', true);
        $settings = $this->settingsFor($router);
        $lines = $settings->manual_lines ?? [];
        $lines[$assetKey] = (int) ($lines[$assetKey] ?? 0) + $nominal;

        // Pastikan semua key dikenal tetap ada
        foreach (array_keys(FinanceSetting::MANUAL_LINES) as $key) {
            $lines[$key] = (int) ($lines[$key] ?? 0);
        }

        $settings->fill([
            'tenant_id' => $tenant->id,
            'manual_lines' => $lines,
        ])->save();

        if ($payFromCash) {
            $label = FinanceSetting::ASSET_PURCHASE_KEYS[$assetKey] ?? $assetKey;
            CashTransaction::query()->create([
                'tenant_id' => $tenant->id,
                'router_id' => $router->id,
                'tanggal' => $valid['tanggal'],
                'tipe' => CashTransaction::TYPE_OUT,
                'kategori' => 'belanja_aset',
                'nominal' => $nominal,
                'metode' => $valid['metode'] ?? 'transfer',
                'keterangan' => trim(($valid['keterangan'] ?? '') !== ''
                    ? (string) $valid['keterangan']
                    : 'Belanja '.$label),
                'ref_asset_key' => $assetKey,
            ]);
        }

        $msg = $payFromCash
            ? 'Belanja aset dicatat: kas berkurang & aset neraca bertambah (tidak masuk laba rugi).'
            : 'Nilai aset neraca ditambah tanpa mengurangi kas (mis. aset awal / hibah).';

        return redirect()
            ->to($this->financeRedirectUrl($request, 'kas'))
            ->with('success', $msg);
    }

    public function destroyCash(Request $request, CashTransaction $cash): RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        if ((int) $cash->router_id !== (int) $router->id) {
            abort(403, 'Akses ditolak.');
        }

        // Rollback nilai aset jika ini belanja modal
        if ($cash->tipe === CashTransaction::TYPE_OUT
            && CashTransaction::isCapex((string) $cash->kategori)
            && filled($cash->ref_asset_key)
            && isset(FinanceSetting::ASSET_PURCHASE_KEYS[$cash->ref_asset_key])) {
            $settings = $this->settingsFor($router);
            $lines = $settings->manual_lines ?? [];
            $key = (string) $cash->ref_asset_key;
            $lines[$key] = max(0, (int) ($lines[$key] ?? 0) - (int) $cash->nominal);
            foreach (array_keys(FinanceSetting::MANUAL_LINES) as $k) {
                $lines[$k] = (int) ($lines[$k] ?? 0);
            }
            $settings->manual_lines = $lines;
            $settings->save();
        }

        $cash->delete();

        return redirect()
            ->to($this->financeRedirectUrl($request, 'kas'))
            ->with('success', 'Transaksi kas dihapus.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        $tenant = current_tenant();
        if (! $tenant?->id) {
            return back()->with('error', 'Tenant tidak ditemukan.');
        }

        $valid = $request->validate([
            'opening_cash' => ['required', 'integer', 'min:0'],
            'opening_equity' => ['required', 'integer', 'min:0'],
            'opening_as_of' => ['nullable', 'date'],
            'manual_lines' => ['nullable', 'array'],
            'manual_lines.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $manual = [];
        foreach (array_keys(FinanceSetting::MANUAL_LINES) as $key) {
            $manual[$key] = (int) ($valid['manual_lines'][$key] ?? 0);
        }

        FinanceSetting::query()->updateOrCreate(
            ['router_id' => $router->id],
            [
                'tenant_id' => $tenant->id,
                'opening_cash' => (int) $valid['opening_cash'],
                'opening_equity' => (int) $valid['opening_equity'],
                'opening_as_of' => $valid['opening_as_of'] ?? null,
                'manual_lines' => $manual,
            ]
        );

        return redirect()
            ->to($this->financeRedirectUrl($request, 'neraca'))
            ->with('success', 'Data neraca disimpan.');
    }

    public function storeDebt(Request $request): RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        $tenant = current_tenant();
        if (! $tenant?->id) {
            return back()->with('error', 'Tenant tidak ditemukan.');
        }

        $valid = $request->validate([
            'tanggal' => ['required', 'date'],
            'keterangan' => ['required', 'string', 'max:255'],
            'nominal' => ['required', 'integer', 'min:1'],
        ]);

        FinanceDebt::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'tanggal' => $valid['tanggal'],
            'keterangan' => $valid['keterangan'],
            'nominal' => (int) $valid['nominal'],
        ]);

        return redirect()
            ->to($this->financeRedirectUrl($request, 'neraca'))
            ->with('success', 'Hutang ditambahkan.');
    }

    public function markDebtPaid(Request $request, FinanceDebt $debt): RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        if ((int) $debt->router_id !== (int) $router->id) {
            abort(403, 'Akses ditolak.');
        }

        $debt->update(['paid_at' => now()]);

        return redirect()
            ->to($this->financeRedirectUrl($request, 'neraca'))
            ->with('success', 'Hutang ditandai lunas.');
    }

    public function destroyDebt(Request $request, FinanceDebt $debt): RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        if ((int) $debt->router_id !== (int) $router->id) {
            abort(403, 'Akses ditolak.');
        }

        $debt->delete();

        return redirect()
            ->to($this->financeRedirectUrl($request, 'neraca'))
            ->with('success', 'Hutang dihapus.');
    }

    public function exportCsv(Request $request): StreamedResponse|RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        [, $range, $year, $month] = $this->parseNav($request);
        $filename = $range === 'yearly'
            ? sprintf('keuangan-%s-%d.csv', $router->name ?: 'router', $year)
            : sprintf('keuangan-%s-%04d-%02d.csv', $router->name ?: 'router', $year, $month);

        return response()->streamDownload(function () use ($router, $range, $year, $month) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['jenis', 'tanggal', 'kategori_metode', 'pelanggan', 'periode_tagihan', 'nominal', 'keterangan']);

            $payQ = BillingPayment::query()
                ->where('router_id', $router->id)
                ->whereNotNull('payment_date')
                ->whereYear('payment_date', $year);
            if ($range === 'monthly') {
                $payQ->whereMonth('payment_date', $month);
            }
            foreach ($payQ->orderBy('payment_date')->get() as $p) {
                fputcsv($out, [
                    'billing',
                    optional($p->payment_date)->format('Y-m-d'),
                    $p->payment_method ?: 'lainnya',
                    $p->display_name ?: $p->customer_name,
                    $p->period,
                    $p->amount,
                    $p->notes,
                ]);
            }

            $cashQ = CashTransaction::query()
                ->where('router_id', $router->id)
                ->whereYear('tanggal', $year);
            if ($range === 'monthly') {
                $cashQ->whereMonth('tanggal', $month);
            }
            foreach ($cashQ->orderBy('tanggal')->get() as $c) {
                fputcsv($out, [
                    'kas_'.$c->tipe,
                    $c->tanggal?->format('Y-m-d'),
                    $c->kategori.' / '.$c->metode,
                    '',
                    '',
                    $c->nominal,
                    $c->keterangan,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request): Response|RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        [$section, $range, $year, $month] = $this->parseNav($request);
        $monthly = $this->buildMonthlySeries($router, $year);
        $selected = $monthly->firstWhere('month', $month) ?? $this->emptyMonth($year, $month);

        $billingIncome = $range === 'monthly'
            ? (int) $selected->billing_income
            : (int) $monthly->sum('billing_income');
        $cashIn = $range === 'monthly'
            ? (int) $selected->cash_in
            : (int) $monthly->sum('cash_in');
        $cashOut = $range === 'monthly'
            ? (int) $selected->cash_out
            : (int) $monthly->sum('cash_out');
        $totalIn = $billingIncome + $cashIn;
        $net = $totalIn - $cashOut;

        $periodEnd = $range === 'yearly'
            ? Carbon::create($year, 12, 31)->endOfDay()
            : Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
        $settings = $this->settingsFor($router);
        $outstanding = $this->estimateOutstanding($router);
        $neraca = $this->buildNeraca($router, $settings, $periodEnd, $outstanding);

        $tenant = current_tenant();
        $periodLabel = $range === 'yearly'
            ? (string) $year
            : Carbon::create($year, $month, 1)->translatedFormat('F Y');

        $pdf = Pdf::loadView('panel.finance-pdf', [
            'tenant' => $tenant,
            'router' => $router,
            'section' => $section,
            'tab' => $range,
            'range' => $range,
            'year' => $year,
            'month' => $month,
            'periodLabel' => $periodLabel,
            'monthly' => $monthly,
            'billingIncome' => $billingIncome,
            'cashIn' => $cashIn,
            'cashOut' => $cashOut,
            'totalIn' => $totalIn,
            'net' => $net,
            'outstanding' => $outstanding,
            'neraca' => $neraca,
            'incomeByCategory' => $this->incomeLinesForPeriod($router, $range, $year, $month),
            'expenseByCategory' => $this->expenseLinesForPeriod($router, $range, $year, $month),
            'labaRugi' => $this->buildLabaRugi($router, $range, $year, $month),
        ])->setPaper('a4', 'portrait');

        $name = $range === 'yearly'
            ? "laporan-keuangan-{$year}.pdf"
            : sprintf('laporan-keuangan-%04d-%02d.pdf', $year, $month);

        return $pdf->download($name);
    }

    protected function financeRedirectUrl(Request $request, ?string $forceSection = null): string
    {
        [$section, $range, $year, $month] = $this->parseNav($request);
        if ($forceSection) {
            $section = $forceSection;
        }

        $query = array_filter([
            'section' => $section,
            'range' => $range,
            'year' => $year,
            'month' => $month,
        ], fn ($v) => $v !== null && $v !== '');

        return panel_route('finance').'?'.http_build_query($query);
    }

    /**
     * @return array{0: string, 1: string, 2: int, 3: int}
     */
    protected function parseNav(Request $request): array
    {
        $legacyTab = (string) $request->query('tab', '');
        $section = (string) $request->query('section', '');
        $range = (string) $request->query('range', '');

        // Backward compat: old ?tab=monthly|yearly
        if (in_array($legacyTab, ['monthly', 'yearly'], true) && $range === '') {
            $range = $legacyTab;
        }
        if (in_array($legacyTab, ['overview', 'pl', 'neraca', 'kas'], true) && $section === '') {
            $section = $legacyTab;
        }

        if (! in_array($section, ['overview', 'pl', 'neraca', 'kas'], true)) {
            $section = 'overview';
        }
        if (! in_array($range, ['monthly', 'yearly'], true)) {
            $range = 'monthly';
        }

        $year = (int) $request->query('year', now()->year);
        if ($year < 2000 || $year > 2100) {
            $year = (int) now()->year;
        }
        $month = (int) $request->query('month', now()->month);
        if ($month < 1 || $month > 12) {
            $month = (int) now()->month;
        }

        return [$section, $range, $year, $month];
    }

    protected function settingsFor(Router $router): FinanceSetting
    {
        $tenant = current_tenant();

        return FinanceSetting::query()->firstOrNew(
            ['router_id' => $router->id],
            [
                'tenant_id' => $tenant?->id ?? 0,
                'opening_cash' => 0,
                'opening_equity' => 0,
                'opening_as_of' => null,
                'manual_lines' => [],
            ]
        );
    }

    protected function buildNeraca(Router $router, FinanceSetting $settings, Carbon $asOf, int $piutang): object
    {
        $asOfDate = $asOf->toDateString();
        $m = fn (string $key): int => $settings->manualAmount($key);

        $billingToDate = (int) BillingPayment::query()
            ->where('router_id', $router->id)
            ->whereNotNull('payment_date')
            ->whereDate('payment_date', '<=', $asOfDate)
            ->sum('amount');

        $cashInToDate = (int) CashTransaction::query()
            ->where('router_id', $router->id)
            ->where('tipe', CashTransaction::TYPE_IN)
            ->whereDate('tanggal', '<=', $asOfDate)
            ->sum('nominal');

        $cashOutToDate = (int) CashTransaction::query()
            ->where('router_id', $router->id)
            ->where('tipe', CashTransaction::TYPE_OUT)
            ->whereDate('tanggal', '<=', $asOfDate)
            ->sum('nominal');

        // Capex mengurangi kas tapi tidak mengurangi laba ditahan (sudah jadi aset).
        $cashOutCapexToDate = (int) CashTransaction::query()
            ->where('router_id', $router->id)
            ->where('tipe', CashTransaction::TYPE_OUT)
            ->whereIn('kategori', CashTransaction::CAPEX_OUT)
            ->whereDate('tanggal', '<=', $asOfDate)
            ->sum('nominal');
        $cashOutOperatingToDate = max(0, $cashOutToDate - $cashOutCapexToDate);

        $kas = (int) $settings->opening_cash + $billingToDate + $cashInToDate - $cashOutToDate;
        $utangUsaha = (int) FinanceDebt::query()
            ->where('router_id', $router->id)
            ->whereNull('paid_at')
            ->whereDate('tanggal', '<=', $asOfDate)
            ->sum('nominal');

        $persediaan = $m('persediaan');
        $prepaid = $m('beban_dibayar_dimuka');
        $totalAktivaLancar = $kas + $piutang + $persediaan + $prepaid;

        $tanah = $m('tanah');
        $bangunan = $m('bangunan');
        $akumBangunan = $m('akum_penyusutan_bangunan');
        $inventaris = $m('inventaris');
        $akumInventaris = $m('akum_penyusutan_inventaris');
        $kendaraan = $m('kendaraan');
        $akumKendaraan = $m('akum_penyusutan_kendaraan');
        $asetOlt = $m('aset_olt');
        $akumOlt = $m('akum_penyusutan_olt');
        $asetOdc = $m('aset_odc_odp');
        $akumOdc = $m('akum_penyusutan_odc');
        $asetKabel = $m('aset_kabel');
        $akumKabel = $m('akum_penyusutan_kabel');
        $totalAktivaTetap = $tanah
            + $bangunan - $akumBangunan
            + $inventaris - $akumInventaris
            + $kendaraan - $akumKendaraan
            + $asetOlt - $akumOlt
            + $asetOdc - $akumOdc
            + $asetKabel - $akumKabel;

        $totalAktiva = $totalAktivaLancar + $totalAktivaTetap;

        $utangGaji = $m('utang_gaji');
        $utangPajak = $m('utang_pajak');
        $bebanAccrued = $m('beban_masih_harus_dibayar');
        $totalKewajibanLancar = $utangUsaha + $utangGaji + $utangPajak + $bebanAccrued;

        $utangBankJp = $m('utang_bank_jp');
        $totalKewajibanJp = $utangBankJp;
        $totalKewajiban = $totalKewajibanLancar + $totalKewajibanJp;

        $modal = (int) $settings->opening_equity;
        $prive = $m('prive');
        $labaDitahan = $billingToDate + $cashInToDate - $cashOutOperatingToDate;
        $totalEkuitas = $modal + $labaDitahan - $prive;
        $totalPassiva = $totalKewajiban + $totalEkuitas;

        return (object) [
            'kas' => $kas,
            'piutang' => $piutang,
            'persediaan' => $persediaan,
            'beban_dibayar_dimuka' => $prepaid,
            'total_aktiva_lancar' => $totalAktivaLancar,
            'tanah' => $tanah,
            'bangunan' => $bangunan,
            'akum_penyusutan_bangunan' => $akumBangunan,
            'inventaris' => $inventaris,
            'akum_penyusutan_inventaris' => $akumInventaris,
            'kendaraan' => $kendaraan,
            'akum_penyusutan_kendaraan' => $akumKendaraan,
            'aset_olt' => $asetOlt,
            'akum_penyusutan_olt' => $akumOlt,
            'aset_odc_odp' => $asetOdc,
            'akum_penyusutan_odc' => $akumOdc,
            'aset_kabel' => $asetKabel,
            'akum_penyusutan_kabel' => $akumKabel,
            'total_aktiva_tetap' => $totalAktivaTetap,
            'total_aset' => $totalAktiva,
            'total_aktiva' => $totalAktiva,
            'utang_usaha' => $utangUsaha,
            'hutang' => $utangUsaha,
            'utang_gaji' => $utangGaji,
            'utang_pajak' => $utangPajak,
            'beban_masih_harus_dibayar' => $bebanAccrued,
            'total_kewajiban_lancar' => $totalKewajibanLancar,
            'utang_bank_jp' => $utangBankJp,
            'total_kewajiban_jp' => $totalKewajibanJp,
            'total_kewajiban' => $totalKewajiban,
            'modal' => $modal,
            'prive' => $prive,
            'laba_ditahan' => $labaDitahan,
            'total_ekuitas' => $totalEkuitas,
            'total_passiva' => $totalPassiva,
            'selisih' => $totalAktiva - $totalPassiva,
            'billing_to_date' => $billingToDate,
            'cash_in_to_date' => $cashInToDate,
            'cash_out_to_date' => $cashOutToDate,
            'cash_out_capex_to_date' => $cashOutCapexToDate,
            'opening_cash' => (int) $settings->opening_cash,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, object{label: string, amount: int}>
     */
    protected function buildLabaRugi(Router $router, string $range, int $year, int $month): object
    {
        $billing = (int) BillingPayment::query()
            ->where('router_id', $router->id)
            ->whereNotNull('payment_date')
            ->whereYear('payment_date', $year)
            ->when($range === 'monthly', fn ($q) => $q->whereMonth('payment_date', $month))
            ->sum('amount');

        $cashInMap = CashTransaction::query()
            ->where('router_id', $router->id)
            ->where('tipe', CashTransaction::TYPE_IN)
            ->whereYear('tanggal', $year)
            ->when($range === 'monthly', fn ($q) => $q->whereMonth('tanggal', $month))
            ->selectRaw('kategori')
            ->selectRaw('SUM(nominal) as total')
            ->groupBy('kategori')
            ->pluck('total', 'kategori')
            ->map(fn ($v) => (int) $v);

        $cashOutMap = CashTransaction::query()
            ->where('router_id', $router->id)
            ->where('tipe', CashTransaction::TYPE_OUT)
            ->whereYear('tanggal', $year)
            ->when($range === 'monthly', fn ($q) => $q->whereMonth('tanggal', $month))
            ->selectRaw('kategori')
            ->selectRaw('SUM(nominal) as total')
            ->groupBy('kategori')
            ->pluck('total', 'kategori')
            ->map(fn ($v) => (int) $v);

        $voucher = (int) ($cashInMap['voucher'] ?? 0);
        $instalasi = (int) ($cashInMap['instalasi'] ?? 0);
        $pendapatanLain = (int) ($cashInMap['lainnya'] ?? 0);
        // Any unexpected cash-in categories roll into lain-lain
        foreach ($cashInMap as $key => $amt) {
            if (! in_array($key, CashTransaction::CATEGORIES_IN, true)) {
                $pendapatanLain += (int) $amt;
            }
        }

        $pendapatanUsaha = [
            (object) ['key' => 'billing', 'label' => 'Pendapatan Langganan PPPoE / Billing', 'amount' => $billing],
            (object) ['key' => 'voucher', 'label' => CashTransaction::categoryLabel('voucher'), 'amount' => $voucher],
            (object) ['key' => 'instalasi', 'label' => CashTransaction::categoryLabel('instalasi'), 'amount' => $instalasi],
            (object) ['key' => 'lainnya_in', 'label' => 'Pendapatan Lain-lain', 'amount' => $pendapatanLain],
        ];
        $totalPendapatan = $billing + $voucher + $instalasi + $pendapatanLain;

        $bebanJaringan = [];
        $totalBebanJaringan = 0;
        foreach (CashTransaction::EXPENSE_NETWORK as $key) {
            $amt = (int) ($cashOutMap[$key] ?? 0);
            $totalBebanJaringan += $amt;
            $bebanJaringan[] = (object) [
                'key' => $key,
                'label' => CashTransaction::categoryLabel($key),
                'amount' => $amt,
            ];
        }

        $bebanUmum = [];
        $totalBebanUmum = 0;
        foreach (CashTransaction::EXPENSE_GENERAL as $key) {
            $amt = (int) ($cashOutMap[$key] ?? 0);
            $totalBebanUmum += $amt;
            $bebanUmum[] = (object) [
                'key' => $key,
                'label' => CashTransaction::categoryLabel($key),
                'amount' => $amt,
            ];
        }
        // Unexpected cash-out categories → lain-lain umum (skip capex)
        foreach ($cashOutMap as $key => $amt) {
            if (CashTransaction::isCapex((string) $key)) {
                continue;
            }
            if (! in_array($key, CashTransaction::CATEGORIES_OUT, true)) {
                $totalBebanUmum += (int) $amt;
                $bebanUmum[] = (object) [
                    'key' => $key,
                    'label' => CashTransaction::categoryLabel($key),
                    'amount' => (int) $amt,
                ];
            }
        }

        $belanjaAset = 0;
        foreach (CashTransaction::CAPEX_OUT as $key) {
            $belanjaAset += (int) ($cashOutMap[$key] ?? 0);
        }

        $totalBeban = $totalBebanJaringan + $totalBebanUmum;
        $labaKotor = $totalPendapatan - $totalBebanJaringan;
        $labaBersih = $totalPendapatan - $totalBeban;

        return (object) [
            'pendapatan_usaha' => $pendapatanUsaha,
            'total_pendapatan' => $totalPendapatan,
            'beban_jaringan' => $bebanJaringan,
            'total_beban_jaringan' => $totalBebanJaringan,
            'laba_kotor' => $labaKotor,
            'beban_umum' => $bebanUmum,
            'total_beban_umum' => $totalBebanUmum,
            'total_beban' => $totalBeban,
            'belanja_aset' => $belanjaAset,
            'laba_bersih' => $labaBersih,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, object{label: string, amount: int}>
     */
    protected function incomeLinesForPeriod(Router $router, string $range, int $year, int $month)
    {
        $lr = $this->buildLabaRugi($router, $range, $year, $month);

        return collect($lr->pendapatan_usaha)
            ->filter(fn ($l) => $l->amount > 0)
            ->values();
    }

    /**
     * @return \Illuminate\Support\Collection<int, object{kategori: string, label: string, total: int}>
     */
    protected function expenseLinesForPeriod(Router $router, string $range, int $year, int $month)
    {
        $lr = $this->buildLabaRugi($router, $range, $year, $month);

        return collect($lr->beban_jaringan)
            ->merge($lr->beban_umum)
            ->filter(fn ($l) => $l->amount > 0)
            ->map(fn ($l) => (object) [
                'kategori' => $l->key,
                'label' => $l->label,
                'total' => $l->amount,
            ])
            ->values();
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    protected function buildMonthlySeries(Router $router, int $year)
    {
        $billingRows = BillingPayment::query()
            ->selectRaw('MONTH(payment_date) as m')
            ->selectRaw('SUM(amount) as total_amount')
            ->selectRaw('COUNT(*) as cnt')
            ->where('router_id', $router->id)
            ->whereNotNull('payment_date')
            ->whereYear('payment_date', $year)
            ->groupBy(DB::raw('MONTH(payment_date)'))
            ->get()
            ->keyBy(fn ($r) => (int) $r->m);

        $cashRows = CashTransaction::query()
            ->selectRaw('MONTH(tanggal) as m')
            ->selectRaw("SUM(CASE WHEN tipe='masuk' THEN nominal ELSE 0 END) as kas_masuk")
            ->selectRaw("SUM(CASE WHEN tipe='keluar' THEN nominal ELSE 0 END) as kas_keluar")
            ->where('router_id', $router->id)
            ->whereYear('tanggal', $year)
            ->groupBy(DB::raw('MONTH(tanggal)'))
            ->get()
            ->keyBy(fn ($r) => (int) $r->m);

        return collect(range(1, 12))->map(function (int $m) use ($year, $billingRows, $cashRows) {
            $b = $billingRows->get($m);
            $c = $cashRows->get($m);
            $billingIncome = (int) ($b->total_amount ?? 0);
            $cashIn = (int) ($c->kas_masuk ?? 0);
            $cashOut = (int) ($c->kas_keluar ?? 0);
            $totalIn = $billingIncome + $cashIn;

            return (object) [
                'year' => $year,
                'month' => $m,
                'billing_income' => $billingIncome,
                'billing_count' => (int) ($b->cnt ?? 0),
                'cash_in' => $cashIn,
                'cash_out' => $cashOut,
                'total_in' => $totalIn,
                'net' => $totalIn - $cashOut,
            ];
        });
    }

    /**
     * @return list<object{label: string, income: int, expense: int, net: int}>
     */
    protected function buildTrendSeries(Router $router, int $months): array
    {
        $series = [];
        $cursor = now()->startOfMonth()->subMonths($months - 1);

        for ($i = 0; $i < $months; $i++) {
            $y = (int) $cursor->year;
            $m = (int) $cursor->month;
            $billing = (int) BillingPayment::query()
                ->where('router_id', $router->id)
                ->whereNotNull('payment_date')
                ->whereYear('payment_date', $y)
                ->whereMonth('payment_date', $m)
                ->sum('amount');
            $cashIn = (int) CashTransaction::query()
                ->where('router_id', $router->id)
                ->where('tipe', CashTransaction::TYPE_IN)
                ->whereYear('tanggal', $y)
                ->whereMonth('tanggal', $m)
                ->sum('nominal');
            $cashOut = (int) CashTransaction::query()
                ->where('router_id', $router->id)
                ->where('tipe', CashTransaction::TYPE_OUT)
                ->whereYear('tanggal', $y)
                ->whereMonth('tanggal', $m)
                ->sum('nominal');
            $income = $billing + $cashIn;
            $series[] = (object) [
                'label' => $cursor->translatedFormat('M Y'),
                'income' => $income,
                'expense' => $cashOut,
                'net' => $income - $cashOut,
            ];
            $cursor->addMonth();
        }

        return $series;
    }

    protected function estimateOutstanding(Router $router): int
    {
        $tenant = current_tenant();
        $grace = $tenant?->isolirGraceDays() ?? (int) config('mikpay.billing.grace_days', 2);

        $total = 0;
        $customers = BillingCustomer::query()
            ->where('router_id', $router->id)
            ->where('monthly_fee', '>', 0)
            ->where('due_day', '>', 0)
            ->get(['customer_name', 'due_day', 'monthly_fee', 'started_at']);

        foreach ($customers as $c) {
            $arrears = BillingArrearsHelper::getUnpaidPeriodsSummary(
                $c->customer_name,
                (int) $c->due_day,
                (int) $c->monthly_fee,
                $router->id,
                $c->started_at,
                $grace
            );
            $total += (int) ($arrears['outstanding_total'] ?? 0);
        }

        return $total;
    }

    /** @return list<int> */
    protected function availableYears(Router $router): array
    {
        $years = collect()
            ->merge(
                BillingPayment::query()
                    ->selectRaw('YEAR(payment_date) as y')
                    ->where('router_id', $router->id)
                    ->whereNotNull('payment_date')
                    ->groupBy(DB::raw('YEAR(payment_date)'))
                    ->pluck('y')
            )
            ->merge(
                CashTransaction::query()
                    ->selectRaw('YEAR(tanggal) as y')
                    ->where('router_id', $router->id)
                    ->groupBy(DB::raw('YEAR(tanggal)'))
                    ->pluck('y')
            )
            ->map(fn ($y) => (int) $y)
            ->filter(fn ($y) => $y > 0)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        if ($years === []) {
            $years = [(int) now()->year];
        }

        return $years;
    }

    protected function emptyMonth(int $year, int $month): object
    {
        return (object) [
            'year' => $year,
            'month' => $month,
            'billing_income' => 0,
            'billing_count' => 0,
            'cash_in' => 0,
            'cash_out' => 0,
            'total_in' => 0,
            'net' => 0,
        ];
    }

    protected function requireActiveRouter(): Router|RedirectResponse
    {
        $router = ActiveRouter::current();

        if (! $router) {
            return redirect()
                ->to(panel_route('settings').'?tab=mikrotik')
                ->with('error', 'Belum ada router MikroTik. Tambah & aktifkan router dulu.');
        }

        if (! $router->isEnabled()) {
            return redirect()
                ->to(panel_route('settings').'?tab=mikrotik')
                ->with('error', 'Router aktif dinonaktifkan. Enable atau pilih router lain.');
        }

        return $router;
    }
}
