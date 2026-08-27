<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Services\SubscriptionPackageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionPricingController extends Controller
{
    /**
     * @return list<string>
     */
    public static function normalizePaymentChannelOrder(?array $stored): array
    {
        $allowed = ['bank_transfer', 'ewallet', 'qris'];
        $order = is_array($stored)
            ? array_values(array_filter($stored, fn ($k) => is_string($k) && in_array($k, $allowed, true)))
            : [];
        foreach ($allowed as $k) {
            if (! in_array($k, $order, true)) {
                $order[] = $k;
            }
        }

        return $order;
    }

    public function edit(): View
    {
        $packages = SubscriptionPackageService::all();
        $featuresText = [];
        foreach ($packages as $key => $pkg) {
            $featuresText[$key] = implode("\n", $pkg['features'] ?? []);
        }

        return view('superadmin.subscription-pricing', [
            'subscriptionPackages' => $packages,
            'subscriptionHeadlines' => SubscriptionPackageService::landingHeadlines(),
            'featuresText' => $featuresText,
            'paymentOrder' => self::normalizePaymentChannelOrder(PlatformSetting::get()->payment_channel_order),
            'subscriptionVerificationWhatsapp' => old(
                'subscription_verification_whatsapp',
                PlatformSetting::get()->subscription_verification_whatsapp ?? ''
            ),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'pricing_eyebrow' => ['nullable', 'string', 'max:80'],
            'pricing_title' => ['nullable', 'string', 'max:200'],
            'pricing_subtitle' => ['nullable', 'string', 'max:400'],
            'subscription_verification_whatsapp' => ['nullable', 'string', 'max:40'],
            'packages' => ['required', 'array'],
            'packages.*.name' => ['required', 'string', 'max:120'],
            'packages.*.badge' => ['nullable', 'string', 'max:40'],
            'packages.*.price' => ['required', 'integer', 'min:0', 'max:999999999'],
            'packages.*.duration' => ['required', 'integer', 'min:1', 'max:20000'],
            'packages.*.duration_label' => ['nullable', 'string', 'max:40'],
            'packages.*.subtitle' => ['nullable', 'string', 'max:255'],
            'packages.*.original_price' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'packages.*.discount_badge' => ['nullable', 'string', 'max:40'],
            'packages.*.featured_ribbon' => ['nullable', 'string', 'max:40'],
            'packages.*.button_label' => ['nullable', 'string', 'max:80'],
            'packages.*.cta_type' => ['required', 'string', 'in:register,checkout,whatsapp'],
            'packages.*.wa_message' => ['nullable', 'string', 'max:255'],
            'packages.*.max_customers' => ['nullable', 'integer', 'min:0', 'max:99999999'],
            'packages.*.max_routers' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'packages.*.features_text' => ['nullable', 'string', 'max:20000'],
            'payment_channel_order' => ['nullable', 'array'],
            'payment_channel_order.*' => ['string', 'in:bank_transfer,ewallet,qris'],
        ]);

        $defaults = SubscriptionPackageService::defaultPackageMeta();
        $input = $request->input('packages', []);
        $meta = [];

        foreach ($defaults as $key => $base) {
            if (! isset($input[$key]) || ! is_array($input[$key])) {
                continue;
            }
            $row = $input[$key];
            $features = array_values(array_filter(array_map(
                'trim',
                preg_split("/\r\n|\n|\r/", (string) ($row['features_text'] ?? '')) ?: []
            )));

            $orig = $row['original_price'] ?? null;
            $meta[$key] = array_merge($base, [
                'name' => (string) $row['name'],
                'badge' => filled($row['badge'] ?? null) ? (string) $row['badge'] : ($base['badge'] ?? null),
                'price' => (int) $row['price'],
                'duration' => (int) $row['duration'],
                'duration_label' => filled($row['duration_label'] ?? null) ? (string) $row['duration_label'] : ($base['duration_label'] ?? null),
                'subtitle' => filled($row['subtitle'] ?? null) ? (string) $row['subtitle'] : null,
                'original_price' => ($orig !== null && $orig !== '') ? (int) $orig : null,
                'discount_badge' => filled($row['discount_badge'] ?? null) ? (string) $row['discount_badge'] : null,
                'featured' => $request->boolean("packages.$key.featured"),
                'featured_ribbon' => filled($row['featured_ribbon'] ?? null) ? (string) $row['featured_ribbon'] : ($base['featured_ribbon'] ?? null),
                'button_label' => filled($row['button_label'] ?? null) ? (string) $row['button_label'] : ($base['button_label'] ?? 'Pilih Paket'),
                'cta_type' => (string) ($row['cta_type'] ?? $base['cta_type'] ?? 'register'),
                'wa_message' => filled($row['wa_message'] ?? null) ? (string) $row['wa_message'] : ($base['wa_message'] ?? null),
                'max_customers' => ($row['max_customers'] ?? '') !== '' ? (int) $row['max_customers'] : null,
                'max_routers' => ($row['max_routers'] ?? '') !== '' ? (int) $row['max_routers'] : null,
                'features' => $features !== [] ? $features : $base['features'],
            ]);
        }

        $settings = PlatformSetting::get();
        $waVerify = trim((string) $request->input('subscription_verification_whatsapp', ''));
        $settings->subscription_verification_whatsapp = $waVerify !== '' ? $waVerify : null;
        $settings->subscription_landing_headlines = [
            'pricing_eyebrow' => trim((string) $request->input('pricing_eyebrow', '')) ?: null,
            'pricing_title' => trim((string) $request->input('pricing_title', '')) ?: null,
            'pricing_subtitle' => trim((string) $request->input('pricing_subtitle', '')) ?: null,
        ];
        $settings->subscription_packages_meta = $meta;

        if ($request->has('payment_channel_order')) {
            $settings->payment_channel_order = self::normalizePaymentChannelOrder(
                $request->input('payment_channel_order')
            );
        }

        $settings->save();

        return redirect()
            ->route('superadmin.subscription-pricing')
            ->with('success', 'Paket langganan dan metode pembayaran berhasil disimpan.');
    }
}
