<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Services\MikrotikService;
use App\Support\ActiveRouter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RouterController extends Controller
{
    public function create(): View
    {
        $this->ensureTenant();

        return view('panel.settings.routers-form', [
            'router' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->ensureTenant();
        $data = $this->validated($request);

        if (strtolower($data['name']) === 'mikpay') {
            return back()->withInput()->with('error', 'Session name tidak boleh "mikpay".');
        }

        $isFirst = ! Router::query()->where('tenant_id', $tenant->id)->exists();
        $makeDefault = (bool) ($data['is_default'] ?? false) || $isFirst;

        if ($makeDefault) {
            Router::query()->where('tenant_id', $tenant->id)->update(['is_default' => false]);
        }

        $router = Router::query()->create([
            ...$data,
            'tenant_id' => $tenant->id,
            'is_default' => $makeDefault,
        ]);

        if ($isFirst || $makeDefault) {
            ActiveRouter::select($router);
        }

        return redirect()
            ->to(panel_route('settings'))
            ->with('success', "Router “{$router->name}” berhasil ditambahkan. Silakan Test Koneksi.");
    }

    public function edit(Router $router): View
    {
        $this->authorizeRouter($router);

        return view('panel.settings.routers-form', [
            'router' => $router,
        ]);
    }

    public function update(Request $request, Router $router): RedirectResponse
    {
        $this->authorizeRouter($router);
        $tenant = $this->ensureTenant();
        $data = $this->validated($request, $router);

        if (strtolower($data['name']) === 'mikpay') {
            return back()->withInput()->with('error', 'Session name tidak boleh "mikpay".');
        }

        if (! empty($data['is_default'])) {
            Router::query()
                ->where('tenant_id', $tenant->id)
                ->where('id', '!=', $router->id)
                ->update(['is_default' => false]);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $makeDefault = (bool) ($data['is_default'] ?? false);

        $router->update([
            ...$data,
            'is_default' => $makeDefault,
        ]);

        if ($makeDefault) {
            ActiveRouter::select($router);
        }

        return redirect()
            ->to(panel_route('settings'))
            ->with('success', "Router “{$router->name}” berhasil diperbarui.");
    }

    public function destroy(Router $router): RedirectResponse
    {
        $this->authorizeRouter($router);
        $name = $router->name;
        ActiveRouter::clearIf($router);
        $router->delete();

        return redirect()
            ->to(panel_route('settings'))
            ->with('success', "Router “{$name}” dihapus.");
    }

    /**
     * Jadikan router ini konteks aktif panel (data pelanggan/isolir mengikuti router ini).
     */
    public function select(Router $router): RedirectResponse
    {
        $this->authorizeRouter($router);

        if (! $router->isEnabled()) {
            return back()->with('error', 'Router dinonaktifkan. Enable dulu sebelum diaktifkan di panel.');
        }

        ActiveRouter::select($router);

        return back()->with(
            'success',
            "Router aktif diganti ke “{$router->displayName()}”. Data pelanggan & isolir akan mengikuti MikroTik ini."
        );
    }

    public function test(Router $router, MikrotikService $mikrotik): RedirectResponse
    {
        $this->authorizeRouter($router);

        if (! $router->isEnabled()) {
            return back()->with('error', 'Router dinonaktifkan. Aktifkan dulu sebelum test.');
        }

        $result = $mikrotik->testConnection($router);

        $router->update([
            'last_check_ok' => $result['ok'],
            'last_checked_at' => now(),
            'last_check_message' => $result['message'],
        ]);

        if ($result['ok']) {
            $active = ActiveRouter::current();
            if (! $active || (int) $active->id === (int) $router->id) {
                ActiveRouter::select($router);
            }
        }

        return back()->with(
            $result['ok'] ? 'success' : 'error',
            $result['message']
        );
    }

    protected function validated(Request $request, ?Router $router = null): array
    {
        $tenant = $this->ensureTenant();

        // Spasi di session → underscore (sama seperti mikpaynew).
        $request->merge([
            'name' => preg_replace('/\s+/', '_', trim((string) $request->input('name', ''))),
            'host' => preg_replace('/\s+/', '', (string) $request->input('host', '')),
        ]);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9_-]+$/',
                Rule::unique('routers', 'name')
                    ->where(fn ($q) => $q->where('tenant_id', $tenant->id))
                    ->ignore($router?->id),
            ],
            'host' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100'],
            'password' => [$router ? 'nullable' : 'required', 'string', 'max:255'],
            'router_name' => ['nullable', 'string', 'max:100'],
            'domain' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'max:10'],
            'expiry_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'status' => ['required', 'in:enable,disable'],
        ], [
            'name.regex' => 'Nama Session hanya boleh huruf, angka, underscore, dan strip (tanpa spasi).',
            'name.unique' => 'Nama Session sudah dipakai di tenant ini.',
            'host.required' => 'IP:Port wajib diisi.',
        ], [
            'name' => 'Nama Session',
            'host' => 'IP:Port',
            'username' => 'Username',
            'password' => 'Password',
            'router_name' => 'Nama Router',
            'domain' => 'Domain',
            'currency' => 'Mata Uang',
            'expiry_days' => 'Expiry (hari)',
        ]);

        [$ip, $port] = $this->parseHostPort($data['host']);
        $data['host'] = $ip;
        $data['port'] = $port;
        $data['router_name'] = $data['router_name'] ?: $data['name'];
        $data['domain'] = $data['domain'] ?? '';
        $data['currency'] = $data['currency'] ?: 'Rp';
        $data['expiry_days'] = (int) ($data['expiry_days'] ?? 10);
        $data['is_default'] = $request->boolean('is_default');
        // SSL tetap dari config / kolom lama; form tidak menampilkan opsi ini.
        $data['use_ssl'] = $router?->use_ssl ?? false;

        return $data;
    }

    /**
     * @return array{0: string, 1: int}
     */
    protected function parseHostPort(string $host): array
    {
        $default = (int) config('mikpay.router.api_port', 8728);
        $host = trim($host);

        if (preg_match('/^\[([^\]]+)\]:(\d+)$/', $host, $m)) {
            return [$m[1], (int) $m[2]];
        }

        if (substr_count($host, ':') === 1) {
            [$ip, $port] = explode(':', $host, 2);
            if ($ip !== '' && is_numeric($port)) {
                $port = (int) $port;
                if ($port >= 1 && $port <= 65535) {
                    return [$ip, $port];
                }
            }
        }

        return [$host, $default];
    }

    protected function ensureTenant(): \App\Models\Tenant
    {
        $tenant = current_tenant();

        if (! $tenant || ! $tenant->id) {
            abort(422, 'Tenant belum tersedia. Pastikan akun terhubung ke tenant.');
        }

        return $tenant;
    }

    protected function authorizeRouter(Router $router): void
    {
        $tenant = $this->ensureTenant();

        if ((int) $router->tenant_id !== (int) $tenant->id) {
            abort(403);
        }
    }
}
