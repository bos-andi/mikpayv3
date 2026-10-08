<?php

namespace App\Http\Controllers;

use App\Jobs\FulfillVoucherOrder;
use App\Models\Router;
use App\Models\Tenant;
use App\Models\VoucherOrder;
use App\Models\VoucherShopItem;
use App\Services\MikrotikService;
use App\Services\VoucherShopService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VoucherShopAdminController extends Controller
{
    private const MAX_ITEMS = 30;

    public function __construct(private VoucherShopService $shop) {}

    public function index(Request $request, MikrotikService $mikrotik): View
    {
        $tenant = $this->tenant();
        $settings = $this->shop->settings($tenant);
        $router = $this->shop->router($tenant);

        $profiles = [];
        $profilesError = '';
        if ($router) {
            $cacheKey = 'voucher_shop.profiles.'.$router->id;
            if ($request->boolean('refresh')) {
                Cache::forget($cacheKey);
            }
            $fetched = Cache::get($cacheKey);
            if (! is_array($fetched)) {
                $res = $mikrotik->fetchHotspotProfiles($router);
                if ($res['ok']) {
                    $fetched = [];
                    foreach ($res['profiles'] as $p) {
                        $name = (string) ($p['name'] ?? '');
                        if ($name === '') {
                            continue;
                        }
                        $meta = self::profileMeta((string) ($p['on-login'] ?? ''));
                        $fetched[] = ['name' => $name, 'validity' => $meta['validity'], 'price' => $meta['sprice'] ?: $meta['price']];
                    }
                    Cache::put($cacheKey, $fetched, now()->addMinutes(10));
                } else {
                    $fetched = [];
                    $profilesError = $res['message'] ?: 'Router tidak terjangkau.';
                }
            }
            $profiles = $fetched;
        }

        $statusFilter = (string) $request->query('status', '');
        $orders = VoucherOrder::query()
            ->where('tenant_id', $tenant->id)
            ->when($statusFilter !== '', fn ($q) => $q->where('status', $statusFilter))
            ->latest()
            ->limit(100)
            ->get();

        $paid = VoucherOrder::query()->where('tenant_id', $tenant->id)->whereIn('status', VoucherOrder::PAID_STATUSES);

        return view('panel.voucher-shop', [
            'tenant' => $tenant,
            'settings' => $settings,
            'availability' => $this->shop->availability($tenant),
            'shopUrl' => $this->shop->shopUrl($tenant),
            'routers' => Router::query()->where('tenant_id', $tenant->id)->orderBy('id')->get(),
            'router' => $router,
            'profiles' => $profiles,
            'profilesError' => $profilesError,
            'items' => VoucherShopItem::query()->where('tenant_id', $tenant->id)->orderBy('sort')->orderBy('price')->get(),
            'orders' => $orders,
            'statusFilter' => $statusFilter,
            'stats' => [
                'today_count' => (clone $paid)->where('paid_at', '>=', today())->count(),
                'today_amount' => (int) (clone $paid)->where('paid_at', '>=', today())->sum('price'),
                'month_amount' => (int) (clone $paid)->where('paid_at', '>=', now()->startOfMonth())->sum('price'),
                'total_amount' => (int) (clone $paid)->sum('price'),
                'failed' => VoucherOrder::query()->where('tenant_id', $tenant->id)->where('status', VoucherOrder::STATUS_FAILED)->count(),
            ],
            'canManage' => $this->canManage(),
            'feePercent' => VoucherShopService::feePercent(),
            'minPrice' => VoucherShopService::minPrice(),
        ]);
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $this->authorizeManage();
        $tenant = $this->tenant();

        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'title' => ['nullable', 'string', 'max:60'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'contact_wa' => ['nullable', 'string', 'regex:/^(\+?62|0)8[0-9]{7,12}$/'],
            'login_url' => ['nullable', 'url:http,https', 'max:200'],
            'router_id' => ['nullable', 'integer', Rule::exists('routers', 'id')->where('tenant_id', $tenant->id)],
            'server' => ['nullable', 'string', 'max:64'],
            'code_length' => ['required', 'integer', 'between:4,10'],
            'code_chars' => ['required', Rule::in(array_keys(VoucherShopService::CHARSETS))],
            'prefix' => ['nullable', 'string', 'max:4', 'regex:/^[A-Za-z0-9]*$/'],
        ], [
            'contact_wa.regex' => 'Nomor WhatsApp tidak valid (contoh: 081234567890).',
            'prefix.regex' => 'Prefix hanya huruf/angka.',
        ]);
        $data['enabled'] = $request->boolean('enabled');

        $this->shop->saveSettings($tenant, $data);

        return redirect()->to(panel_route('vouchers.shop'))->with('success', 'Pengaturan toko voucher disimpan.');
    }

    public function storeItem(Request $request): RedirectResponse
    {
        $this->authorizeManage();
        $tenant = $this->tenant();

        if (VoucherShopItem::query()->where('tenant_id', $tenant->id)->count() >= self::MAX_ITEMS) {
            return back()->with('error', 'Maksimal '.self::MAX_ITEMS.' paket.');
        }

        VoucherShopItem::query()->create(['tenant_id' => $tenant->id] + $this->validateItem($request));

        return redirect()->to(panel_route('vouchers.shop'))->with('success', 'Paket ditambahkan ke toko.');
    }

    public function updateItem(Request $request, int $item): RedirectResponse
    {
        $this->authorizeManage();
        $row = $this->item($item);
        $row->update($this->validateItem($request));

        return redirect()->to(panel_route('vouchers.shop'))->with('success', 'Paket diperbarui.');
    }

    public function destroyItem(int $item): RedirectResponse
    {
        $this->authorizeManage();
        $this->item($item)->delete();

        return redirect()->to(panel_route('vouchers.shop'))->with('success', 'Paket dihapus dari toko.');
    }

    public function retryOrder(int $order): RedirectResponse
    {
        $this->authorizeManage();
        $row = VoucherOrder::query()->where('tenant_id', $this->tenant()->id)->whereKey($order)->firstOrFail();

        if (! in_array($row->status, [VoucherOrder::STATUS_PAID, VoucherOrder::STATUS_FAILED], true)) {
            return back()->with('error', 'Pesanan ini tidak perlu diproses ulang.');
        }

        $result = $this->shop->fulfill($row);
        if ($result['ok']) {
            return back()->with('success', 'Voucher '.$row->reference.' berhasil dibuat: '.$row->fresh()->voucher_code);
        }

        if ($row->status === VoucherOrder::STATUS_FAILED) {
            $row->forceFill(['status' => VoucherOrder::STATUS_PAID])->save();
        }
        FulfillVoucherOrder::dispatch($row->id)->delay(now()->addSeconds(30));

        return back()->with('error', 'Belum berhasil: '.$result['message'].' — sistem akan mencoba lagi otomatis.');
    }

    /**
     * Baca meta Mikhmon dari on-login profile: :put (",expmode,price,validity,sprice,,lock,").
     *
     * @return array{expmode: string, price: int, validity: string, sprice: int}
     */
    public static function profileMeta(string $onlogin): array
    {
        if (preg_match('/:put\s*\(\s*",([^"]*)"/', $onlogin, $m)) {
            $parts = explode(',', $m[1]);

            return [
                'expmode' => (string) ($parts[0] ?? ''),
                'price' => (int) ($parts[1] ?? 0),
                'validity' => (string) ($parts[2] ?? ''),
                'sprice' => (int) ($parts[3] ?? 0),
            ];
        }

        return ['expmode' => '', 'price' => 0, 'validity' => '', 'sprice' => 0];
    }

    private function validateItem(Request $request): array
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:160'],
            'profile' => ['required', 'string', 'max:128'],
            'price' => ['required', 'integer', 'min:'.VoucherShopService::minPrice(), 'max:10000000'],
            'limit_uptime' => ['nullable', 'string', 'max:32', 'regex:/^([0-9]+[wdhms])+$/'],
            'limit_quota_mb' => ['nullable', 'integer', 'min:1', 'max:10000000'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'price.min' => 'Harga minimal Rp '.number_format(VoucherShopService::minPrice(), 0, ',', '.').' (batas QRIS).',
            'limit_uptime.regex' => 'Format batas waktu seperti 3h, 1d, 1d12h.',
        ]);

        return [
            'label' => $data['label'],
            'description' => $data['description'] ?? null,
            'profile' => $data['profile'],
            'price' => (int) $data['price'],
            'limit_uptime' => $data['limit_uptime'] ?? null,
            'limit_bytes' => ! empty($data['limit_quota_mb']) ? (int) $data['limit_quota_mb'] * 1024 * 1024 : null,
            'sort' => (int) ($data['sort'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function item(int $id): VoucherShopItem
    {
        return VoucherShopItem::query()->where('tenant_id', $this->tenant()->id)->whereKey($id)->firstOrFail();
    }

    private function tenant(): Tenant
    {
        $tenant = current_tenant();
        abort_unless($tenant instanceof Tenant, 404);

        return $tenant;
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user && ($user->isTenantAdmin() || $user->isSuperAdmin());
    }

    private function authorizeManage(): void
    {
        abort_unless($this->canManage(), 403, 'Hanya admin tenant yang dapat mengelola toko voucher.');
    }
}
