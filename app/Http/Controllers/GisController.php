<?php

namespace App\Http\Controllers;

use App\Models\BillingCustomer;
use App\Models\Odp;
use App\Models\OdpPort;
use App\Models\Router;
use App\Services\MikrotikService;
use App\Support\ActiveRouter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GisController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        return view('panel.gis', [
            'router' => $router,
            'gisConfig' => [
                'dataUrl' => panel_route('gis.data'),
                'storeUrl' => panel_route('gis.store'),
                'csrf' => csrf_token(),
                'routerName' => $router->displayName(),
                'ratios' => Odp::RATIO_OPTIONS,
            ],
        ]);
    }

    public function data(MikrotikService $mikrotik): JsonResponse
    {
        $router = ActiveRouter::current();
        if (! $router) {
            return response()->json(['ok' => false, 'message' => 'Belum ada router aktif.'], 422);
        }

        $ppp = $this->pppStatusMap($mikrotik, $router);
        $billingMap = BillingCustomer::query()
            ->where('router_id', $router->id)
            ->get()
            ->keyBy(fn (BillingCustomer $b) => strtolower($b->customer_name));

        $odps = Odp::query()
            ->where('router_id', $router->id)
            ->with(['ports', 'parent', 'children'])
            ->orderByRaw("FIELD(type, 'olt', 'odc', 'odp')")
            ->orderBy('code')
            ->get();

        $byId = $odps->keyBy('id');

        $nodes = $odps->map(function (Odp $odp) use ($ppp, $billingMap, $byId) {
            $ports = [];
            if ($odp->hasCustomerPorts()) {
                foreach ($odp->ports as $port) {
                    $ports[] = $this->serializePort($port, $ppp['by_name'], $billingMap);
                }
            }

            $used = $odp->hasCustomerPorts()
                ? collect($ports)->where('occupied', true)->count()
                : $odp->children->count();

            $parent = $odp->parent_id ? $byId->get($odp->parent_id) : null;

            return [
                'id' => $odp->id,
                'type' => $odp->type,
                'type_label' => $odp->typeLabel(),
                'code' => $odp->code,
                'name' => $odp->name,
                'label' => $odp->displayLabel(),
                'latitude' => (float) $odp->latitude,
                'longitude' => (float) $odp->longitude,
                'port_count' => (int) $odp->port_count,
                'ratio' => $odp->ratioLabel(),
                'used_ports' => $used,
                'child_count' => $odp->children->count(),
                'parent_id' => $odp->parent_id,
                'parent_code' => $parent?->code,
                'parent_type' => $parent?->type,
                'parent_label' => $parent?->displayLabel(),
                'pon_label' => $odp->pon_label,
                'notes' => $odp->notes,
                'ports' => $ports,
                'has_customer_ports' => $odp->hasCustomerPorts(),
                'has_ratio' => $odp->hasRatio(),
                'update_url' => panel_route('gis.update', $odp),
                'destroy_url' => panel_route('gis.destroy', $odp),
                'ports_url' => panel_route('gis.ports.update', $odp),
            ];
        })->values();

        $odpNodes = $nodes->where('type', Odp::TYPE_ODP);
        $stats = [
            'odp_count' => $odpNodes->count(),
            'odc_count' => $nodes->where('type', Odp::TYPE_ODC)->count(),
            'olt_count' => $nodes->where('type', Odp::TYPE_OLT)->count(),
            'port_total' => (int) $odpNodes->sum('port_count'),
            'port_used' => (int) $odpNodes->sum('used_ports'),
        ];

        return response()->json([
            'ok' => true,
            'router' => $router->displayName(),
            'ppp_ok' => $ppp['ok'],
            'ppp_message' => $ppp['message'],
            'stats' => $stats,
            'nodes' => $nodes,
            'customers' => $this->customerOptions($ppp['by_name'], $billingMap),
            'ratios' => Odp::RATIO_OPTIONS,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $router = ActiveRouter::current();
        $tenant = current_tenant();
        if (! $router || ! $tenant?->id) {
            return response()->json(['ok' => false, 'message' => 'Belum ada router aktif.'], 422);
        }

        $data = $request->validate([
            'type' => ['required', Rule::in(Odp::types())],
            'code' => [
                'required',
                'string',
                'max:64',
                Rule::unique('odps', 'code')->where(fn ($q) => $q->where('router_id', $router->id)),
            ],
            'name' => ['nullable', 'string', 'max:120'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'port_count' => ['nullable', 'integer', Rule::in(Odp::RATIO_OPTIONS)],
            'parent_id' => ['nullable', 'integer'],
            'pon_label' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $type = $data['type'];
        $portCount = $type === Odp::TYPE_OLT
            ? 0
            : (int) ($data['port_count'] ?? 8);

        $parentId = $this->resolveParentId($router->id, $type, $data['parent_id'] ?? null);
        if ($parentId instanceof JsonResponse) {
            return $parentId;
        }

        $odp = Odp::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'parent_id' => $parentId,
            'type' => $type,
            'code' => trim($data['code']),
            'name' => filled($data['name'] ?? null) ? trim((string) $data['name']) : null,
            'latitude' => round((float) $data['latitude'], 7),
            'longitude' => round((float) $data['longitude'], 7),
            'port_count' => $portCount,
            'pon_label' => filled($data['pon_label'] ?? null) ? trim((string) $data['pon_label']) : null,
            'notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
        ]);

        if ($odp->hasCustomerPorts() && $portCount > 0) {
            $this->insertEmptyPorts($odp->id, 1, $portCount);
        }

        return response()->json([
            'ok' => true,
            'message' => $odp->typeLabel()." “{$odp->code}” ditambahkan.",
            'id' => $odp->id,
        ]);
    }

    public function update(Request $request, Odp $odp): JsonResponse
    {
        if ($err = $this->authorizeOdp($odp)) {
            return $err;
        }

        $data = $request->validate([
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:64',
                Rule::unique('odps', 'code')
                    ->where(fn ($q) => $q->where('router_id', $odp->router_id))
                    ->ignore($odp->id),
            ],
            'name' => ['nullable', 'string', 'max:120'],
            'latitude' => ['sometimes', 'required', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'required', 'numeric', 'between:-180,180'],
            'port_count' => ['nullable', 'integer', Rule::in(Odp::RATIO_OPTIONS)],
            'parent_id' => ['nullable'],
            'pon_label' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (array_key_exists('code', $data)) {
            $odp->code = trim($data['code']);
        }
        if (array_key_exists('name', $data)) {
            $odp->name = filled($data['name']) ? trim((string) $data['name']) : null;
        }
        if (array_key_exists('latitude', $data)) {
            $odp->latitude = round((float) $data['latitude'], 7);
        }
        if (array_key_exists('longitude', $data)) {
            $odp->longitude = round((float) $data['longitude'], 7);
        }
        if (array_key_exists('pon_label', $data)) {
            $odp->pon_label = filled($data['pon_label']) ? trim((string) $data['pon_label']) : null;
        }
        if (array_key_exists('notes', $data)) {
            $odp->notes = filled($data['notes']) ? trim((string) $data['notes']) : null;
        }

        if (array_key_exists('parent_id', $data)) {
            $rawParent = $data['parent_id'];
            $parentId = ($rawParent === null || $rawParent === '' || (int) $rawParent === 0)
                ? null
                : (int) $rawParent;
            $resolved = $this->resolveParentId((int) $odp->router_id, $odp->type, $parentId, $odp->id);
            if ($resolved instanceof JsonResponse) {
                return $resolved;
            }
            $odp->parent_id = $resolved;
        }

        if (array_key_exists('port_count', $data) && $odp->hasRatio()) {
            $newCount = (int) $data['port_count'];
            $sync = $this->syncPortCount($odp, $newCount);
            if ($sync instanceof JsonResponse) {
                return $sync;
            }
            $odp->port_count = $newCount;
        }

        $odp->save();

        return response()->json([
            'ok' => true,
            'message' => $odp->typeLabel()." “{$odp->code}” diperbarui.",
        ]);
    }

    public function destroy(Odp $odp): JsonResponse
    {
        if ($err = $this->authorizeOdp($odp)) {
            return $err;
        }

        $childCount = Odp::query()->where('parent_id', $odp->id)->count();
        if ($childCount > 0) {
            return response()->json([
                'ok' => false,
                'message' => "Tidak bisa hapus: masih ada {$childCount} perangkat terhubung. Pindahkan/hapus anaknya dulu.",
            ], 422);
        }

        $label = $odp->code;
        $kind = $odp->typeLabel();
        $odp->delete();

        return response()->json([
            'ok' => true,
            'message' => "{$kind} “{$label}” dihapus.",
        ]);
    }

    public function updatePort(Request $request, Odp $odp): JsonResponse
    {
        if ($err = $this->authorizeOdp($odp)) {
            return $err;
        }

        if (! $odp->hasCustomerPorts()) {
            return response()->json(['ok' => false, 'message' => 'Hanya ODP yang punya mapping pelanggan.'], 422);
        }

        $data = $request->validate([
            'port_number' => ['required', 'integer', 'min:1', 'max:64'],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'clear' => ['sometimes', 'boolean'],
            'clear_location' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $port = OdpPort::query()
            ->where('odp_id', $odp->id)
            ->where('port_number', (int) $data['port_number'])
            ->first();

        if (! $port) {
            return response()->json(['ok' => false, 'message' => 'Port tidak ditemukan.'], 404);
        }

        if (! empty($data['clear'])) {
            $port->customer_name = null;
            $port->latitude = null;
            $port->longitude = null;
            $port->notes = null;
            $port->save();

            return response()->json(['ok' => true, 'message' => "Port {$port->port_number} dikosongkan."]);
        }

        if (! empty($data['clear_location'])) {
            $port->latitude = null;
            $port->longitude = null;
            $port->save();

            return response()->json(['ok' => true, 'message' => "Lokasi port {$port->port_number} dihapus."]);
        }

        if (array_key_exists('customer_name', $data)) {
            $name = trim((string) ($data['customer_name'] ?? ''));
            if ($name === '') {
                $port->customer_name = null;
            } else {
                $taken = OdpPort::query()
                    ->whereHas('odp', fn ($q) => $q->where('router_id', $odp->router_id))
                    ->whereRaw('LOWER(customer_name) = ?', [strtolower($name)])
                    ->where('id', '!=', $port->id)
                    ->first();

                if ($taken) {
                    return response()->json([
                        'ok' => false,
                        'message' => "Username “{$name}” sudah terpasang di ODP lain (port {$taken->port_number}).",
                    ], 422);
                }

                $port->customer_name = $name;
            }
        }

        if (isset($data['latitude'], $data['longitude']) && $data['latitude'] !== null && $data['longitude'] !== null) {
            $port->latitude = round((float) $data['latitude'], 7);
            $port->longitude = round((float) $data['longitude'], 7);
        }

        if (array_key_exists('notes', $data)) {
            $port->notes = filled($data['notes']) ? trim((string) $data['notes']) : null;
        }

        $port->save();

        return response()->json([
            'ok' => true,
            'message' => "Port {$port->port_number} disimpan.",
        ]);
    }

    /**
     * @return int|null|JsonResponse
     */
    protected function resolveParentId(int $routerId, string $type, mixed $parentId, ?int $selfId = null): int|JsonResponse|null
    {
        if ($type === Odp::TYPE_OLT) {
            return null;
        }

        if ($parentId === null || $parentId === '' || (int) $parentId === 0) {
            return null;
        }

        $parentId = (int) $parentId;
        if ($selfId && $parentId === $selfId) {
            return response()->json(['ok' => false, 'message' => 'Parent tidak boleh diri sendiri.'], 422);
        }

        $parent = Odp::query()
            ->where('router_id', $routerId)
            ->where('id', $parentId)
            ->first();

        if (! $parent) {
            return response()->json(['ok' => false, 'message' => 'Parent tidak ditemukan.'], 422);
        }

        if ($type === Odp::TYPE_ODC && ! $parent->isOlt()) {
            return response()->json(['ok' => false, 'message' => 'ODC harus terhubung ke OLT.'], 422);
        }

        if ($type === Odp::TYPE_ODP && ! ($parent->isOlt() || $parent->isOdc())) {
            return response()->json(['ok' => false, 'message' => 'ODP harus terhubung ke ODC atau OLT.'], 422);
        }

        // Cegah cycle sederhana: parent tidak boleh anak dari self
        if ($selfId) {
            $walk = $parent;
            $guard = 0;
            while ($walk && $guard < 20) {
                if ((int) $walk->id === $selfId) {
                    return response()->json(['ok' => false, 'message' => 'Parent membentuk siklus — pilih parent lain.'], 422);
                }
                $walk = $walk->parent_id
                    ? Odp::query()->find($walk->parent_id)
                    : null;
                $guard++;
            }
        }

        return $parentId;
    }

    protected function syncPortCount(Odp $odp, int $newCount): ?JsonResponse
    {
        if (! $odp->hasCustomerPorts()) {
            return null;
        }

        $current = (int) $odp->port_count;
        if ($newCount === $current) {
            return null;
        }

        if ($newCount > $current) {
            $this->insertEmptyPorts($odp->id, $current + 1, $newCount);

            return null;
        }

        $occupiedHigh = OdpPort::query()
            ->where('odp_id', $odp->id)
            ->where('port_number', '>', $newCount)
            ->whereNotNull('customer_name')
            ->where('customer_name', '!=', '')
            ->count();

        if ($occupiedHigh > 0) {
            return response()->json([
                'ok' => false,
                'message' => "Tidak bisa turunkan rasio ke 1:{$newCount}: masih ada pelanggan di port > {$newCount}.",
            ], 422);
        }

        OdpPort::query()
            ->where('odp_id', $odp->id)
            ->where('port_number', '>', $newCount)
            ->delete();

        return null;
    }

    protected function insertEmptyPorts(int $odpId, int $from, int $to): void
    {
        $rows = [];
        $now = now();
        for ($i = $from; $i <= $to; $i++) {
            $rows[] = [
                'odp_id' => $odpId,
                'port_number' => $i,
                'customer_name' => null,
                'latitude' => null,
                'longitude' => null,
                'notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($rows !== []) {
            OdpPort::query()->insert($rows);
        }
    }

    /**
     * @return array{ok: bool, message: string, by_name: array<string, array{name: string, online: bool, isolir: bool, disabled: bool, profile: string}>}
     */
    protected function pppStatusMap(MikrotikService $mikrotik, Router $router): array
    {
        try {
            $result = $mikrotik->fetchPppCustomers($router);
            $byName = [];

            foreach ($result['customers'] ?? [] as $c) {
                $name = (string) ($c['name'] ?? '');
                if ($name === '') {
                    continue;
                }
                $byName[strtolower($name)] = [
                    'name' => $name,
                    'online' => (bool) ($c['online'] ?? false),
                    'isolir' => (bool) ($c['isolir'] ?? false),
                    'disabled' => (bool) ($c['disabled'] ?? false),
                    'profile' => (string) ($c['profile'] ?? ''),
                ];
            }

            return [
                'ok' => (bool) ($result['ok'] ?? false),
                'message' => (string) ($result['message'] ?? ''),
                'by_name' => $byName,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => $e->getMessage(),
                'by_name' => [],
            ];
        }
    }

    /**
     * @param  array<string, array{name: string, online: bool, isolir: bool, disabled: bool, profile: string}>  $pppByName
     * @param  Collection<string, BillingCustomer>  $billingMap
     * @return array<string, mixed>
     */
    protected function serializePort(OdpPort $port, array $pppByName, Collection $billingMap): array
    {
        $name = (string) ($port->customer_name ?? '');
        $key = strtolower($name);
        $status = $key !== '' ? ($pppByName[$key] ?? null) : null;
        $billing = $key !== '' ? $billingMap->get($key) : null;

        $occupied = $name !== '';
        $isolir = (bool) ($status['isolir'] ?? false) || (bool) ($status['disabled'] ?? false);
        $online = (bool) ($status['online'] ?? false);

        $state = 'empty';
        if ($occupied) {
            $state = $isolir ? 'isolir' : ($online ? 'online' : 'offline');
        }

        return [
            'id' => $port->id,
            'port_number' => $port->port_number,
            'customer_name' => $occupied ? $name : null,
            'display_name' => $billing?->display_name,
            'phone' => $billing?->phone,
            'latitude' => $port->latitude,
            'longitude' => $port->longitude,
            'has_location' => $port->hasCoordinates(),
            'notes' => $port->notes,
            'occupied' => $occupied,
            'online' => $online,
            'isolir' => $isolir,
            'state' => $state,
            'profile' => $status['profile'] ?? null,
        ];
    }

    /**
     * @param  array<string, array{name: string, online: bool, isolir: bool, disabled: bool, profile: string}>  $pppByName
     * @param  Collection<string, BillingCustomer>  $billingMap
     * @return array<int, array{name: string, label: string}>
     */
    protected function customerOptions(array $pppByName, Collection $billingMap): array
    {
        $items = [];

        foreach ($pppByName as $key => $row) {
            $name = $row['name'];
            $billing = $billingMap->get($key);
            $items[$key] = [
                'name' => $name,
                'label' => $billing?->display_name
                    ? "{$name} — {$billing->display_name}"
                    : $name,
            ];
        }

        foreach ($billingMap as $key => $billing) {
            if (isset($items[$key])) {
                continue;
            }
            $name = $billing->customer_name;
            $items[$key] = [
                'name' => $name,
                'label' => $billing->display_name
                    ? "{$name} — {$billing->display_name}"
                    : $name,
            ];
        }

        ksort($items);

        return array_values($items);
    }

    protected function authorizeOdp(Odp $odp): ?JsonResponse
    {
        $tenant = current_tenant();
        $router = ActiveRouter::current();

        if (! $tenant?->id || (int) $odp->tenant_id !== (int) $tenant->id) {
            return response()->json(['ok' => false, 'message' => 'Akses ditolak.'], 403);
        }

        if (! $router || (int) $odp->router_id !== (int) $router->id) {
            return response()->json(['ok' => false, 'message' => 'Bukan milik router aktif.'], 403);
        }

        return null;
    }

    protected function requireActiveRouter(): Router|RedirectResponse
    {
        $router = ActiveRouter::current();
        if (! $router) {
            return redirect()
                ->to(panel_route('settings'))
                ->with('error', 'Belum ada router aktif. Atur router dulu di Pengaturan.');
        }

        return $router;
    }
}
