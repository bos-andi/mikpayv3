<?php

namespace App\Http\Controllers;

use App\Models\BillingCustomer;
use App\Services\CustomerImportService;
use App\Services\MikrotikService;
use App\Support\ActiveRouter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomersController extends Controller
{
    public function index(Request $request, MikrotikService $mikrotik): View|RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        $result = $mikrotik->fetchPppCustomers($router);
        $billingMap = $this->billingMapForRouter($router->id);

        $q = trim((string) $request->query('q', ''));
        $filter = (string) $request->query('filter', 'all');
        $customers = collect($result['customers'])->map(function (array $c) use ($billingMap) {
            $billing = $billingMap->get(strtolower($c['name']));

            return array_merge($c, [
                'display_name' => $billing?->display_name,
                'phone' => $billing?->phone,
                'email' => $billing?->email,
                'zone' => $billing?->zone,
                'package_label' => ($c['profile'] ?? null) ?: ($billing?->package_label),
                'monthly_fee' => $billing?->monthly_fee ?? 0,
                'due_day' => $billing?->due_day ?? 0,
                'started_at' => $billing?->started_at,
                'address' => $billing?->address,
                'notes' => $billing?->notes ?: ($c['comment'] ?? null),
                'billing_configured' => $billing?->isBillingConfigured() ?? false,
                'has_billing' => (bool) $billing,
            ]);
        });

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $customers = $customers->filter(function (array $c) use ($needle) {
                $hay = mb_strtolower(implode(' ', [
                    $c['name'] ?? '',
                    $c['display_name'] ?? '',
                    $c['phone'] ?? '',
                    $c['email'] ?? '',
                    $c['zone'] ?? '',
                    $c['package_label'] ?? '',
                    $c['profile'] ?? '',
                    $c['comment'] ?? '',
                    $c['notes'] ?? '',
                    $c['address'] ?? '',
                    $c['ip'] ?? '',
                ]));

                return str_contains($hay, $needle);
            })->values();
        }

        if ($filter === 'online') {
            $customers = $customers->where('online', true)->values();
        } elseif ($filter === 'offline') {
            $customers = $customers->where('online', false)->values();
        } elseif ($filter === 'isolir') {
            $customers = $customers->where('isolir', true)->values();
        } elseif ($filter === 'belum') {
            $customers = $customers->where('billing_configured', false)->values();
        }

        $configured = $billingMap->filter(fn (BillingCustomer $b) => $b->isBillingConfigured())->count();

        return view('panel.customers', [
            'router' => $router,
            'customers' => $customers,
            'profiles' => $result['profiles'],
            'stats' => array_merge($result['stats'], [
                'billing_set' => $configured,
                'billing_unset' => max(0, ($result['stats']['total'] ?? 0) - $configured),
            ]),
            'fetchOk' => $result['ok'],
            'fetchMessage' => $result['message'],
            'q' => $q,
            'filter' => $filter,
        ]);
    }

    public function edit(Request $request, MikrotikService $mikrotik): View|RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        $name = trim((string) $request->query('name', ''));
        if ($name === '') {
            return redirect()->to(panel_route('customers'))->with('error', 'Username PPP tidak valid.');
        }

        $customer = BillingCustomer::query()
            ->where('router_id', $router->id)
            ->where('customer_name', $name)
            ->first();

        $mt = $mikrotik->findSecretByName($router, $name);
        $profiles = $mt['profiles'] ?? [];
        $currentProfile = (string) ($mt['secret']['profile'] ?? '');

        // Pastikan profile saat ini ada di list dropdown.
        if ($currentProfile !== '' && ! in_array($currentProfile, $profiles, true)) {
            $profiles[] = $currentProfile;
            sort($profiles);
        }

        $selectedPackage = old('package_label', $currentProfile ?: ($customer?->package_label ?? ''));

        return view('panel.customers-edit', [
            'router' => $router,
            'customer_name' => $name,
            'customer' => $customer,
            'profiles' => $profiles,
            'currentProfile' => $currentProfile,
            'selectedPackage' => $selectedPackage,
            'mikrotikOk' => $mt['ok'] && $mt['secret'],
            'mikrotikMessage' => $mt['message'] ?? null,
            'secretId' => (string) ($mt['secret']['id'] ?? ''),
        ]);
    }

    public function update(Request $request, MikrotikService $mikrotik): RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        $tenant = current_tenant();
        if (! $tenant?->id) {
            abort(422, 'Tenant belum tersedia.');
        }

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'zone' => ['nullable', 'string', 'max:100'],
            'package_label' => ['required', 'string', 'max:255'],
            'monthly_fee' => ['nullable', 'integer', 'min:0'],
            'due_day' => ['required', 'integer', 'min:0', 'max:31'],
            'started_at' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'customer_name' => 'Username PPP',
            'display_name' => 'Nama tampilan',
            'phone' => 'No. HP',
            'email' => 'Email',
            'zone' => 'Zona',
            'package_label' => 'Paket (PPP Profile)',
            'monthly_fee' => 'Tarif per bulan',
            'due_day' => 'Tanggal jatuh tempo',
            'started_at' => 'Mulai layanan',
            'address' => 'Alamat',
            'notes' => 'Catatan',
        ]);

        $mt = $mikrotik->findSecretByName($router, $data['customer_name']);
        if (! $mt['ok'] || ! $mt['secret']) {
            return back()->withInput()->with('error', $mt['message'] ?: 'PPP secret tidak ditemukan di MikroTik.');
        }

        $profiles = $mt['profiles'] ?? [];
        if ($profiles !== [] && ! in_array($data['package_label'], $profiles, true)) {
            return back()->withInput()->with('error', 'Paket harus dipilih dari daftar PPP Profile MikroTik.');
        }

        $currentProfile = (string) ($mt['secret']['profile'] ?? '');
        $profileMsg = null;
        if ($data['package_label'] !== $currentProfile) {
            $sync = $mikrotik->setSecretProfile(
                $router,
                (string) $mt['secret']['id'],
                $data['customer_name'],
                $data['package_label']
            );
            if (! $sync['ok']) {
                return back()->withInput()->with('error', 'Gagal update profile di MikroTik: '.$sync['message']);
            }
            $profileMsg = $sync['message'];
        }

        $dueDay = (int) $data['due_day'];
        $startedAt = $data['started_at'] ?? null;
        $existing = BillingCustomer::query()
            ->where('router_id', $router->id)
            ->where('customer_name', $data['customer_name'])
            ->first();

        // Pelanggan baru: kosongkan Mulai layanan → otomatis hari ini (tagihan mulai bulan daftar).
        if ($startedAt === null || $startedAt === '') {
            $startedAt = $existing?->started_at?->format('Y-m-d');
        }
        if ($dueDay > 0 && ($startedAt === null || $startedAt === '')) {
            $startedAt = now()->toDateString();
        }
        if ($dueDay <= 0) {
            $startedAt = $startedAt ?: null;
        }

        $customer = BillingCustomer::query()->updateOrCreate(
            [
                'router_id' => $router->id,
                'customer_name' => $data['customer_name'],
            ],
            [
                'tenant_id' => $tenant->id,
                'display_name' => $data['display_name'] ?: null,
                'phone' => $data['phone'] ?: null,
                'email' => $data['email'] ?: null,
                'zone' => $data['zone'] ?: null,
                'package_label' => $data['package_label'],
                'monthly_fee' => (int) ($data['monthly_fee'] ?? 0),
                'due_day' => $dueDay,
                'started_at' => $startedAt ?: null,
                'address' => $data['address'] ?: null,
                'notes' => $data['notes'] ?: null,
            ]
        );

        if ($dueDay > 0 && ! $customer->public_code) {
            app(\App\Services\PaymentLinkService::class)->ensurePublicCode($customer);
        }

        $msg = "Data pelanggan “{$data['customer_name']}” disimpan.";
        if ($profileMsg) {
            $msg .= ' '.$profileMsg;
        }

        return redirect()
            ->to(panel_route('customers'))
            ->with('success', $msg);
    }

    public function store(Request $request, MikrotikService $mikrotik): RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        $tenant = current_tenant();
        if (! $tenant?->id) {
            abort(422, 'Tenant belum tersedia.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9._@\-]+$/'],
            'password' => ['required', 'string', 'min:4', 'max:64', 'not_regex:/\s/'],
            'service' => ['required', 'in:pppoe,any,pptp,l2tp,ovpn,sstp'],
            'profile' => ['required', 'string', 'max:100'],
            'remote_address' => ['nullable', 'ip'],
            'local_address' => ['nullable', 'ip'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'zone' => ['nullable', 'string', 'max:100'],
            'monthly_fee' => ['nullable', 'integer', 'min:0'],
            'due_day' => ['nullable', 'integer', 'min:0', 'max:31'],
            'address' => ['nullable', 'string', 'max:2000'],
        ], [
            'name.regex' => 'Username PPP hanya boleh huruf, angka, titik, minus, garis bawah, dan @.',
            'password.not_regex' => 'Password PPP tidak boleh mengandung spasi.',
        ], [
            'name' => 'Username PPP',
            'password' => 'Password PPP',
            'service' => 'Service',
            'profile' => 'Paket (PPP Profile)',
            'remote_address' => 'Remote address',
            'local_address' => 'Local address',
            'display_name' => 'Nama pelanggan',
            'phone' => 'No. HP',
            'zone' => 'Zona',
            'monthly_fee' => 'Tarif per bulan',
            'due_day' => 'Tanggal jatuh tempo',
            'address' => 'Alamat',
        ]);

        $result = $mikrotik->addPppSecret($router, [
            'name' => $data['name'],
            'password' => $data['password'],
            'service' => $data['service'],
            'profile' => $data['profile'],
            'local_address' => $data['local_address'] ?? null,
            'remote_address' => $data['remote_address'] ?? null,
            'comment' => $data['display_name'] ?? null,
        ]);

        if (! $result['ok']) {
            return back()->withInput($request->except('password'))->with('error', $result['message']);
        }

        $dueDay = (int) ($data['due_day'] ?? 0);
        $hasBilling = $dueDay > 0
            || (int) ($data['monthly_fee'] ?? 0) > 0
            || filled($data['display_name'] ?? null)
            || filled($data['phone'] ?? null)
            || filled($data['zone'] ?? null)
            || filled($data['address'] ?? null);

        if ($hasBilling) {
            $customer = BillingCustomer::query()->updateOrCreate(
                [
                    'router_id' => $router->id,
                    'customer_name' => $data['name'],
                ],
                [
                    'tenant_id' => $tenant->id,
                    'display_name' => $data['display_name'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'zone' => $data['zone'] ?? null,
                    'package_label' => $data['profile'],
                    'monthly_fee' => (int) ($data['monthly_fee'] ?? 0),
                    'due_day' => $dueDay,
                    'started_at' => $dueDay > 0 ? now()->toDateString() : null,
                    'address' => $data['address'] ?? null,
                ]
            );

            if ($dueDay > 0 && ! $customer->public_code) {
                app(\App\Services\PaymentLinkService::class)->ensurePublicCode($customer);
            }
        }

        $msg = $result['message'];
        if ($hasBilling) {
            $msg .= ' Data tagihan juga disimpan.';
        }

        return redirect()
            ->to(panel_route('customers').'?'.http_build_query(['q' => $data['name']]))
            ->with('success', $msg);
    }

    public function downloadTemplate(MikrotikService $mikrotik, CustomerImportService $importer): StreamedResponse|RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        $result = $mikrotik->fetchPppCustomers($router);
        if (! $result['ok']) {
            return redirect()
                ->to(panel_route('customers'))
                ->with('error', $result['message']);
        }

        $billingMap = $this->billingMapForRouter($router->id);
        $spreadsheet = $importer->buildTemplate(
            $result['customers'],
            $billingMap,
            $router->displayName().' ('.$router->endpointLabel().')'
        );

        $filename = 'mikpay-template-pelanggan-'.$router->name.'-'.now()->format('Y-m-d').'.xlsx';

        return $importer->download($spreadsheet, $filename);
    }

    public function import(Request $request, MikrotikService $mikrotik, CustomerImportService $importer): RedirectResponse|JsonResponse
    {
        $wantsJson = $request->expectsJson() || $request->ajax();

        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            if ($wantsJson) {
                return response()->json(['ok' => false, 'message' => 'Pilih router aktif dulu.'], 422);
            }

            return $router;
        }

        $tenant = current_tenant();
        if (! $tenant?->id) {
            if ($wantsJson) {
                return response()->json(['ok' => false, 'message' => 'Tenant belum tersedia.'], 422);
            }
            abort(422, 'Tenant belum tersedia.');
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ], [], [
            'file' => 'File Excel',
        ]);

        $result = $mikrotik->fetchPppCustomers($router);
        if (! $result['ok']) {
            if ($wantsJson) {
                return response()->json(['ok' => false, 'message' => $result['message']], 422);
            }

            return back()->with('error', $result['message']);
        }

        $allowed = [];
        foreach ($result['customers'] as $c) {
            $n = strtolower((string) ($c['name'] ?? ''));
            if ($n !== '') {
                $allowed[$n] = true;
            }
        }

        $path = $request->file('file')->getRealPath();
        $import = $importer->importFromPath($path, $router, $tenant, $allowed);

        $msg = "Import selesai: {$import['saved']} disimpan";
        if ($import['skipped'] > 0) {
            $msg .= ", {$import['skipped']} dilewati";
        }
        $msg .= '.';

        $errorPreview = null;
        if ($import['errors'] !== []) {
            $errorPreview = implode(' · ', array_slice($import['errors'], 0, 5));
            if (count($import['errors']) > 5) {
                $errorPreview .= ' …';
            }
        }

        if ($wantsJson) {
            return response()->json([
                'ok' => true,
                'message' => $msg,
                'saved' => (int) $import['saved'],
                'skipped' => (int) $import['skipped'],
                'errors' => $import['errors'],
                'error_preview' => $errorPreview,
                'redirect' => panel_route('customers'),
            ]);
        }

        if ($errorPreview !== null) {
            return redirect()
                ->to(panel_route('customers'))
                ->with('success', $msg)
                ->with('error', $errorPreview);
        }

        return redirect()
            ->to(panel_route('customers'))
            ->with('success', $msg);
    }

    public function isolir(Request $request, MikrotikService $mikrotik): RedirectResponse
    {
        return $this->toggleIsolir($request, $mikrotik, true);
    }

    public function open(Request $request, MikrotikService $mikrotik): RedirectResponse
    {
        return $this->toggleIsolir($request, $mikrotik, false);
    }

    protected function toggleIsolir(Request $request, MikrotikService $mikrotik, bool $isolir): RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        $data = $request->validate([
            'id' => ['required', 'string', 'max:64'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $result = $mikrotik->setSecretIsolir($router, $data['id'], $data['name'], $isolir);

        return back()->with(
            $result['ok'] ? 'success' : 'error',
            $result['message']
        );
    }

    /**
     * @return Collection<string, BillingCustomer>
     */
    protected function billingMapForRouter(int $routerId): Collection
    {
        return BillingCustomer::query()
            ->where('router_id', $routerId)
            ->get()
            ->keyBy(fn (BillingCustomer $b) => strtolower($b->customer_name));
    }

    protected function requireActiveRouter(): \App\Models\Router|RedirectResponse
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
