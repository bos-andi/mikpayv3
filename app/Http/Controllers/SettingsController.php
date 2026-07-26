<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function updateXendit(Request $request): RedirectResponse
    {
        $tenant = $this->requirePersistedTenant();

        $data = $request->validate([
            'active' => ['required', 'in:platform,xendit,midtrans,doku,tripay'],
            // Xendit own
            'xendit_secret_key' => ['nullable', 'string', 'max:255'],
            'xendit_callback_token' => ['nullable', 'string', 'max:255'],
            'clear_xendit_secret_key' => ['nullable', 'boolean'],
            'clear_xendit_callback_token' => ['nullable', 'boolean'],
            // Midtrans
            'midtrans_server_key' => ['nullable', 'string', 'max:255'],
            'midtrans_client_key' => ['nullable', 'string', 'max:255'],
            'midtrans_merchant_id' => ['nullable', 'string', 'max:100'],
            'midtrans_is_production' => ['nullable', 'boolean'],
            'clear_midtrans_server_key' => ['nullable', 'boolean'],
            // Doku
            'doku_client_id' => ['nullable', 'string', 'max:255'],
            'doku_secret_key' => ['nullable', 'string', 'max:255'],
            'doku_is_production' => ['nullable', 'boolean'],
            'clear_doku_secret_key' => ['nullable', 'boolean'],
            // Tripay
            'tripay_api_key' => ['nullable', 'string', 'max:255'],
            'tripay_private_key' => ['nullable', 'string', 'max:255'],
            'tripay_merchant_code' => ['nullable', 'string', 'max:100'],
            'tripay_is_production' => ['nullable', 'boolean'],
            'clear_tripay_api_key' => ['nullable', 'boolean'],
            'clear_tripay_private_key' => ['nullable', 'boolean'],
        ]);

        $active = $data['active'];
        $tenant->metaSet('payment.active', $active);
        // Legacy keys for compatibility
        $tenant->metaSet('payment.mode', $active === 'platform' ? 'platform' : 'own');
        $tenant->metaSet('payment.gateway', $active === 'platform' ? 'xendit' : $active);

        // --- Xendit own secrets ---
        if ($request->boolean('clear_xendit_secret_key')) {
            $tenant->clearMetaSecret('xendit.secret_key');
        } elseif (! empty($data['xendit_secret_key'])) {
            $tenant->setEncryptedMetaSecret('xendit.secret_key', $data['xendit_secret_key']);
        }
        if ($request->boolean('clear_xendit_callback_token')) {
            $tenant->clearMetaSecret('xendit.callback_token');
        } elseif (! empty($data['xendit_callback_token'])) {
            $tenant->setEncryptedMetaSecret('xendit.callback_token', $data['xendit_callback_token']);
        }

        // --- Midtrans ---
        if ($request->boolean('clear_midtrans_server_key')) {
            $tenant->clearMetaSecret('midtrans.server_key');
        } elseif (! empty($data['midtrans_server_key'])) {
            $tenant->setEncryptedMetaSecret('midtrans.server_key', $data['midtrans_server_key']);
        }
        if (array_key_exists('midtrans_client_key', $data) && $data['midtrans_client_key'] !== null && $data['midtrans_client_key'] !== '') {
            $tenant->metaSet('midtrans.client_key', $data['midtrans_client_key']);
        }
        if (array_key_exists('midtrans_merchant_id', $data)) {
            $tenant->metaSet('midtrans.merchant_id', $data['midtrans_merchant_id'] ?: null);
        }
        $tenant->metaSet('midtrans.is_production', $request->boolean('midtrans_is_production'));

        // --- Doku ---
        if (array_key_exists('doku_client_id', $data) && $data['doku_client_id'] !== null && $data['doku_client_id'] !== '') {
            $tenant->metaSet('doku.client_id', $data['doku_client_id']);
        }
        if ($request->boolean('clear_doku_secret_key')) {
            $tenant->clearMetaSecret('doku.secret_key');
        } elseif (! empty($data['doku_secret_key'])) {
            $tenant->setEncryptedMetaSecret('doku.secret_key', $data['doku_secret_key']);
        }
        $tenant->metaSet('doku.is_production', $request->boolean('doku_is_production'));

        // --- Tripay ---
        if ($request->boolean('clear_tripay_api_key')) {
            $tenant->clearMetaSecret('tripay.api_key');
        } elseif (! empty($data['tripay_api_key'])) {
            $tenant->setEncryptedMetaSecret('tripay.api_key', $data['tripay_api_key']);
        }
        if ($request->boolean('clear_tripay_private_key')) {
            $tenant->clearMetaSecret('tripay.private_key');
        } elseif (! empty($data['tripay_private_key'])) {
            $tenant->setEncryptedMetaSecret('tripay.private_key', $data['tripay_private_key']);
        }
        if (array_key_exists('tripay_merchant_code', $data)) {
            $tenant->metaSet('tripay.merchant_code', $data['tripay_merchant_code'] ?: null);
        }
        $tenant->metaSet('tripay.is_production', $request->boolean('tripay_is_production'));

        $tenant->save();

        $label = \App\Support\PaymentGateway::label($active);

        return redirect()
            ->to(panel_route('settings').'?tab=payment')
            ->with('success', 'Payment gateway aktif: '.$label.'. Hanya satu gateway yang aktif.');
    }

    public function updateWhatsapp(Request $request): RedirectResponse
    {
        $tenant = $this->requirePersistedTenant();
        $defs = \App\Support\WaapiTemplates::definitions();
        $keys = array_keys($defs);

        $rules = [
            'enabled' => ['nullable', 'boolean'],
            'api_token' => ['nullable', 'string', 'max:255'],
            'clear_api_token' => ['nullable', 'boolean'],
            'templates' => ['nullable', 'array'],
            'auto' => ['nullable', 'array'],
        ];
        foreach ($keys as $key) {
            $rules['templates.'.$key] = ['nullable', 'string', 'max:4000'];
            $rules['auto.'.$key] = ['nullable', 'boolean'];
        }

        $data = $request->validate($rules);

        $tenant->metaSet('waapi.enabled', $request->boolean('enabled'));

        if ($request->boolean('clear_api_token')) {
            $tenant->clearMetaSecret('waapi.api_token');
            $tenant->clearMetaSecret('fonnte.api_token');
        } elseif (! empty($data['api_token'])) {
            $tenant->setEncryptedMetaSecret('waapi.api_token', $data['api_token']);
        }

        foreach ($keys as $key) {
            $body = trim((string) ($data['templates'][$key] ?? ''));
            $tenant->metaSet('waapi.templates.'.$key, $body !== '' ? $body : null);

            if ($defs[$key]['auto']) {
                $tenant->metaSet('waapi.auto.'.$key, $request->boolean('auto.'.$key));
            }
        }

        // Sync legacy single field = template billing_link
        $billingTpl = trim((string) ($data['templates']['billing_link'] ?? ''));
        $tenant->metaSet('waapi.reminder_template', $billingTpl !== '' ? $billingTpl : null);

        $tenant->save();

        return redirect()
            ->to(panel_route('settings').'?tab=whatsapp')
            ->with('success', 'Pengaturan WhatsApp & template reminder disimpan.');
    }

    public function updateBilling(Request $request): RedirectResponse
    {
        $tenant = $this->requirePersistedTenant();

        $data = $request->validate([
            'grace_days' => ['required', 'integer', 'min:0', 'max:60'],
        ], [], [
            'grace_days' => 'Masa tenggang isolir',
        ]);

        $tenant->metaSet('billing.grace_days', (int) $data['grace_days']);
        $tenant->save();

        return redirect()
            ->to(panel_route('settings').'?tab=billing')
            ->with('success', 'Masa tenggang isolir disimpan: '.$data['grace_days'].' hari setelah jatuh tempo.');
    }

    public function testWhatsapp(Request $request, \App\Services\WaApiService $waapi): RedirectResponse
    {
        $tenant = $this->requirePersistedTenant();

        $data = $request->validate([
            'test_phone' => ['required', 'string', 'max:30'],
        ], [], [
            'test_phone' => 'Nomor uji',
        ]);

        $token = $tenant->plainWaapiToken();
        if ($token === '') {
            return redirect()
                ->to(panel_route('settings').'?tab=whatsapp')
                ->with('error', 'Token WaAPI belum diisi.');
        }

        $result = $waapi->sendText(
            $token,
            $data['test_phone'],
            'Tes WhatsApp Gateway MikPay (WaAPI) — '.now()->format('d/m/Y H:i')
        );

        return redirect()
            ->to(panel_route('settings').'?tab=whatsapp')
            ->with(
                $result['ok'] ? 'success' : 'error',
                $result['ok'] ? 'Pesan uji diantrikan ke WaAPI.' : ('Gagal: '.$result['message'])
            );
    }

    protected function requirePersistedTenant(): \App\Models\Tenant
    {
        $tenant = current_tenant();
        if (! $tenant?->id) {
            abort(422, 'Tenant belum tersedia.');
        }

        return $tenant;
    }
}
