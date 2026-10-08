<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\VoucherOrder;
use App\Models\VoucherShopItem;
use App\Services\VoucherShopService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman publik {tenant}.mikpay.link/voucher — pelanggan WiFi membeli voucher sendiri via QRIS.
 */
class VoucherShopController extends Controller
{
    public function __construct(private VoucherShopService $shop) {}

    public function index(): View
    {
        $tenant = $this->tenant();
        $availability = $this->shop->availability($tenant);

        $items = $availability['open']
            ? VoucherShopItem::query()
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->where('price', '>=', VoucherShopService::minPrice())
                ->orderBy('sort')
                ->orderBy('price')
                ->get()
            : collect();

        return view('voucher-shop.index', [
            'shopTenant' => $tenant,
            'settings' => $this->shop->settings($tenant),
            'availability' => $availability,
            'items' => $items,
            'feePercent' => VoucherShopService::feePercent(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();

        $data = $request->validate([
            'item_id' => ['required', 'integer'],
            'phone' => ['nullable', 'string', 'regex:/^(\+?62|0)8[0-9]{7,12}$/'],
        ], [
            'phone.regex' => 'Nomor WhatsApp tidak valid (contoh: 081234567890).',
        ]);

        $item = VoucherShopItem::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($data['item_id'])
            ->first();
        if (! $item) {
            return back()->with('error', 'Paket tidak ditemukan.');
        }

        $phone = isset($data['phone']) ? preg_replace('/[^0-9+]/', '', (string) $data['phone']) : null;
        $result = $this->shop->createOrder($tenant, $item, $phone ?: null, $request->ip());
        if (! $result['ok']) {
            return back()->withInput()->with('error', $result['message']);
        }

        return redirect()->route('tenant.voucher-shop.show', [
            'tenant' => $tenant->subdomain,
            'token' => $result['order']->token,
        ]);
    }

    public function show(string $token): View
    {
        $tenant = $this->tenant();
        $order = $this->order($tenant, $token);
        $this->shop->checkPayment($order);
        $order->refresh();

        return view('voucher-shop.order', [
            'shopTenant' => $tenant,
            'settings' => $this->shop->settings($tenant),
            'order' => $order,
        ]);
    }

    public function status(string $token): JsonResponse
    {
        $tenant = $this->tenant();
        $order = $this->order($tenant, $token);
        $this->shop->checkPayment($order);
        $order->refresh();

        $status = $order->isPaymentExpired() ? VoucherOrder::STATUS_EXPIRED : $order->status;
        $payload = ['status' => $status, 'label' => $order->statusLabel()];
        if ($order->status === VoucherOrder::STATUS_FULFILLED) {
            $payload['code'] = $order->voucher_code;
            $payload['password'] = $order->voucher_password;
        }

        return response()->json($payload)->header('Cache-Control', 'no-store');
    }

    private function tenant(): Tenant
    {
        $tenant = current_tenant();
        $hostSub = strtolower(explode('.', request()->getHost())[0]);
        abort_unless($tenant instanceof Tenant && strtolower((string) $tenant->subdomain) === $hostSub, 404);

        return $tenant;
    }

    private function order(Tenant $tenant, string $token): VoucherOrder
    {
        $order = VoucherOrder::query()
            ->where('tenant_id', $tenant->id)
            ->where('token', $token)
            ->first();
        abort_unless($order, 404);

        return $order;
    }
}
