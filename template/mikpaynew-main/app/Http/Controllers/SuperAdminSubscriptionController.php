<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Services\SubscriptionPackageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SuperAdminSubscriptionController extends Controller
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
        $featRaw = BusinessSetting::get()->subscription_premium_features;
        $subscriptionFeaturesText = is_array($featRaw) && $featRaw !== []
            ? implode("\n", $featRaw)
            : implode("\n", SubscriptionPackageService::defaultPremiumFeatures());

        $paymentOrder = self::normalizePaymentChannelOrder(BusinessSetting::get()->payment_channel_order);

        return view('superadmin.subscription-pricing', [
            'subscriptionPackages' => SubscriptionPackageService::all(),
            'subscriptionHeadlines' => SubscriptionPackageService::landingHeadlines(),
            'subscriptionFeaturesText' => $subscriptionFeaturesText,
            'paymentOrder' => $paymentOrder,
            'subscriptionVerificationWhatsapp' => old(
                'subscription_verification_whatsapp',
                BusinessSetting::get()->subscription_verification_whatsapp ?? ''
            ),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'subscription_pricing_title' => 'nullable|string|max:160',
            'subscription_pricing_subtitle' => 'nullable|string|max:255',
            'subscription_premium_tagline' => 'nullable|string|max:255',
            'subscription_premium_features_text' => 'nullable|string|max:50000',
            'monthly_name' => 'required|string|max:120',
            'monthly_price' => 'required|integer|min:0|max:999999999',
            'monthly_duration' => 'required|integer|min:1|max:20000',
            'monthly_duration_label' => 'nullable|string|max:80',
            'monthly_button_label' => 'nullable|string|max:80',
            'monthly5_name' => 'required|string|max:120',
            'monthly5_price' => 'required|integer|min:0|max:999999999',
            'monthly5_duration' => 'required|integer|min:1|max:20000',
            'monthly5_subtitle' => 'nullable|string|max:160',
            'monthly5_original_price' => 'nullable|integer|min:0|max:999999999',
            'monthly5_discount_badge' => 'nullable|string|max:40',
            'monthly5_featured_ribbon' => 'nullable|string|max:40',
            'monthly5_highlight_footer' => 'nullable|string|max:255',
            'monthly5_button_label' => 'nullable|string|max:80',
            'payment_channel_order' => 'nullable|array',
            'payment_channel_order.*' => 'string|in:bank_transfer,ewallet,qris',
        ]);

        $settings = BusinessSetting::get();

        $waVerify = trim((string) $request->input('subscription_verification_whatsapp', ''));
        $settings->subscription_verification_whatsapp = $waVerify !== '' ? $waVerify : null;

        $featuresText = (string) $request->input('subscription_premium_features_text', '');
        $lines = array_values(array_filter(array_map('trim', preg_split("/\r\n|\n|\r/", $featuresText))));
        $settings->subscription_premium_features = $lines !== [] ? $lines : null;

        $settings->subscription_landing_headlines = [
            'pricing_title' => trim((string) $request->input('subscription_pricing_title', '')) ?: null,
            'pricing_subtitle' => trim((string) $request->input('subscription_pricing_subtitle', '')) ?: null,
            'premium_tagline' => trim((string) $request->input('subscription_premium_tagline', '')) ?: null,
        ];

        $defaults = SubscriptionPackageService::defaultPackageMeta();
        $orig5 = $request->input('monthly5_original_price');
        $settings->subscription_packages_meta = [
            'monthly' => array_merge($defaults['monthly'], [
                'name' => $request->input('monthly_name'),
                'price' => (int) $request->input('monthly_price'),
                'duration' => (int) $request->input('monthly_duration'),
                'duration_label' => $request->filled('monthly_duration_label') ? (string) $request->input('monthly_duration_label') : null,
                'subtitle' => null,
                'original_price' => null,
                'discount_badge' => null,
                'featured' => false,
                'featured_ribbon' => null,
                'button_label' => $request->filled('monthly_button_label') ? (string) $request->input('monthly_button_label') : ($defaults['monthly']['button_label'] ?? 'Pilih Paket'),
                'highlight_footer' => null,
            ]),
            'monthly5' => array_merge($defaults['monthly5'], [
                'name' => $request->input('monthly5_name'),
                'price' => (int) $request->input('monthly5_price'),
                'duration' => (int) $request->input('monthly5_duration'),
                'duration_label' => null,
                'subtitle' => $request->filled('monthly5_subtitle') ? (string) $request->input('monthly5_subtitle') : null,
                'original_price' => $orig5 !== null && $orig5 !== '' ? (int) $orig5 : null,
                'discount_badge' => $request->filled('monthly5_discount_badge') ? (string) $request->input('monthly5_discount_badge') : null,
                'featured' => $request->boolean('monthly5_featured'),
                'featured_ribbon' => $request->filled('monthly5_featured_ribbon') ? (string) $request->input('monthly5_featured_ribbon') : ($defaults['monthly5']['featured_ribbon'] ?? 'POPULER'),
                'button_label' => $request->filled('monthly5_button_label') ? (string) $request->input('monthly5_button_label') : ($defaults['monthly5']['button_label'] ?? 'Pilih Paket Populer'),
                'highlight_footer' => $request->filled('monthly5_highlight_footer') ? (string) $request->input('monthly5_highlight_footer') : null,
            ]),
        ];

        if ($request->has('payment_channel_order')) {
            $allowedChannels = ['bank_transfer', 'ewallet', 'qris'];
            $order = $request->input('payment_channel_order', []);
            if (is_array($order)) {
                $order = array_values(array_filter($order, fn ($k) => is_string($k) && in_array($k, $allowedChannels, true)));
                foreach ($allowedChannels as $k) {
                    if (! in_array($k, $order, true)) {
                        $order[] = $k;
                    }
                }
                $settings->payment_channel_order = $order;
            }
        }

        $settings->save();

        return redirect()
            ->route('superadmin.subscription-pricing')
            ->with('success', 'Paket, fitur premium, dan urutan metode pembayaran berhasil disimpan.');
    }
}
