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
        return view('superadmin.subscription-pricing', [
            'subscriptionPackages' => SubscriptionPackageService::all(),
            'subscriptionHeadlines' => SubscriptionPackageService::landingHeadlines(),
            'featuresText' => implode("\n", SubscriptionPackageService::sharedFeatures()),
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
            'features_text' => ['nullable', 'string', 'max:20000'],
            'packages' => ['required', 'array'],
            'packages.*.name' => ['required', 'string', 'max:120'],
            'packages.*.price' => ['required', 'integer', 'min:1000', 'max:999999999'],
            'packages.*.subtitle' => ['nullable', 'string', 'max:255'],
            'packages.*.featured_ribbon' => ['nullable', 'string', 'max:40'],
            'packages.*.button_label' => ['nullable', 'string', 'max:80'],
            'payment_channel_order' => ['nullable', 'array'],
            'payment_channel_order.*' => ['string', 'in:bank_transfer,ewallet,qris'],
        ]);

        $features = array_values(array_filter(array_map(
            'trim',
            preg_split("/\r\n|\n|\r/", (string) $request->input('features_text', '')) ?: []
        )));

        $input = $request->input('packages', []);
        $meta = [];

        foreach (SubscriptionPackageService::defaultPackageMeta() as $key => $base) {
            if (! isset($input[$key]) || ! is_array($input[$key])) {
                continue;
            }
            $row = $input[$key];

            $meta[$key] = [
                'name' => (string) $row['name'],
                'price' => (int) $row['price'],
                'subtitle' => filled($row['subtitle'] ?? null) ? (string) $row['subtitle'] : null,
                'featured' => $request->boolean("packages.$key.featured"),
                'featured_ribbon' => filled($row['featured_ribbon'] ?? null) ? (string) $row['featured_ribbon'] : null,
                'button_label' => filled($row['button_label'] ?? null) ? (string) $row['button_label'] : $base['button_label'],
                'features' => $features !== [] ? $features : $base['features'],
            ];
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
