<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Services\LandingContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function edit(): View
    {
        $content = LandingContentService::all();

        return view('superadmin.landing', [
            'content' => $content,
            'faqsText' => LandingContentService::faqsText(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'hero_badge' => ['nullable', 'string', 'max:160'],
            'hero_title' => ['nullable', 'string', 'max:200'],
            'hero_title_highlight' => ['nullable', 'string', 'max:120'],
            'hero_subtitle' => ['nullable', 'string', 'max:1000'],
            'hero_cta_primary' => ['nullable', 'string', 'max:80'],
            'hero_cta_secondary' => ['nullable', 'string', 'max:80'],
            'cta_title' => ['nullable', 'string', 'max:200'],
            'cta_subtitle' => ['nullable', 'string', 'max:500'],
            'cta_button' => ['nullable', 'string', 'max:80'],
            'faqs_text' => ['nullable', 'string', 'max:50000'],
        ]);

        $faqs = LandingContentService::parseFaqsText((string) ($data['faqs_text'] ?? ''));

        $settings = PlatformSetting::get();
        $settings->landing_content = [
            'hero_badge' => trim((string) ($data['hero_badge'] ?? '')) ?: null,
            'hero_title' => trim((string) ($data['hero_title'] ?? '')) ?: null,
            'hero_title_highlight' => trim((string) ($data['hero_title_highlight'] ?? '')) ?: null,
            'hero_subtitle' => trim((string) ($data['hero_subtitle'] ?? '')) ?: null,
            'hero_cta_primary' => trim((string) ($data['hero_cta_primary'] ?? '')) ?: null,
            'hero_cta_secondary' => trim((string) ($data['hero_cta_secondary'] ?? '')) ?: null,
            'cta_title' => trim((string) ($data['cta_title'] ?? '')) ?: null,
            'cta_subtitle' => trim((string) ($data['cta_subtitle'] ?? '')) ?: null,
            'cta_button' => trim((string) ($data['cta_button'] ?? '')) ?: null,
            'faqs' => $faqs !== [] ? $faqs : null,
        ];
        $settings->save();

        return redirect()
            ->route('superadmin.landing')
            ->with('success', 'Konten landing berhasil disimpan.');
    }
}
