<?php

namespace App\Http\Controllers;

use App\Models\Odp;
use App\Models\OltDevice;
use App\Services\MikrotikService;
use App\Services\OltMonitorService;
use App\Support\ActiveRouter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OltController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $tenant = current_tenant();
        $router = ActiveRouter::current();
        if (! $tenant || ! $router) {
            return back()->with('error', 'Tenant atau router tidak ditemukan.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'vendor' => ['required', 'string', 'max:32'],
            'management_ip' => ['nullable', 'string', 'max:45'],
            'web_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'pon_count' => ['nullable', 'integer', 'min:0', 'max:64'],
            'odp_id' => [
                'nullable',
                'integer',
                Rule::exists('odps', 'id')->where(fn ($q) => $q
                    ->where('tenant_id', $tenant->id)
                    ->where('router_id', $router->id)
                    ->where('type', Odp::TYPE_OLT)),
            ],
        ]);

        OltDevice::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'odp_id' => $data['odp_id'] ?? null,
            'name' => $data['name'],
            'vendor' => strtolower($data['vendor']),
            'management_ip' => $data['management_ip'] ?? null,
            'web_port' => (int) ($data['web_port'] ?? 80),
            'pon_count' => (int) ($data['pon_count'] ?? 0),
            'status' => OltDevice::STATUS_UNKNOWN,
        ]);

        return back()->with('success', 'Perangkat OLT berhasil ditambahkan.');
    }

    public function update(Request $request, OltDevice $olt): RedirectResponse
    {
        $tenant = current_tenant();
        $router = ActiveRouter::current();
        if (! $tenant || ! $router || (int) $olt->tenant_id !== (int) $tenant->id) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'vendor' => ['required', 'string', 'max:32'],
            'management_ip' => ['nullable', 'string', 'max:45'],
            'web_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'pon_count' => ['nullable', 'integer', 'min:0', 'max:64'],
            'odp_id' => [
                'nullable',
                'integer',
                Rule::exists('odps', 'id')->where(fn ($q) => $q
                    ->where('tenant_id', $tenant->id)
                    ->where('router_id', $router->id)
                    ->where('type', Odp::TYPE_OLT)),
            ],
        ]);

        $olt->update([
            'odp_id' => $data['odp_id'] ?? null,
            'name' => $data['name'],
            'vendor' => strtolower($data['vendor']),
            'management_ip' => $data['management_ip'] ?? null,
            'web_port' => (int) ($data['web_port'] ?? 80),
            'pon_count' => (int) ($data['pon_count'] ?? 0),
        ]);

        return back()->with('success', 'Konfigurasi OLT diperbarui.');
    }

    public function destroy(OltDevice $olt): RedirectResponse
    {
        $tenant = current_tenant();
        if (! $tenant || (int) $olt->tenant_id !== (int) $tenant->id) {
            abort(403);
        }

        $olt->delete();

        return back()->with('success', 'Perangkat OLT dihapus.');
    }

    public function poll(OltDevice $olt, OltMonitorService $monitor): JsonResponse
    {
        $tenant = current_tenant();
        if (! $tenant || (int) $olt->tenant_id !== (int) $tenant->id) {
            abort(403);
        }

        $result = $monitor->pollDevice($olt->fresh() ?? $olt);

        return response()->json($result);
    }

    public function sync(OltMonitorService $monitor, MikrotikService $mikrotik): JsonResponse
    {
        $tenant = current_tenant();
        $router = ActiveRouter::current();
        if (! $tenant || ! $router) {
            return response()->json(['ok' => false, 'message' => 'Tenant atau router tidak ditemukan.']);
        }

        $result = $monitor->syncFromRouter($tenant, $router, $mikrotik);

        return response()->json([
            'ok' => true,
            'message' => $result['message'],
            'result' => $result,
        ]);
    }

    public function pollAll(OltMonitorService $monitor, MikrotikService $mikrotik): JsonResponse
    {
        $tenant = current_tenant();
        if (! $tenant) {
            abort(403);
        }

        $router = ActiveRouter::current();
        $summary = $monitor->pollTenant($tenant, $router?->id, $mikrotik);

        return response()->json([
            'ok' => true,
            'summary' => $summary,
        ]);
    }
}
