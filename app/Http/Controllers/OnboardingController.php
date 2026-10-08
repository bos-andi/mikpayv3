<?php

namespace App\Http\Controllers;

use App\Services\OnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function index(OnboardingService $onboarding): View
    {
        $tenant = current_tenant();
        abort_unless($tenant?->id, 404);
        abort_unless($onboarding->isEligible($tenant), 404);

        return view('panel.onboarding', [
            'checklist' => $onboarding->checklist($tenant),
            'tenant' => $tenant,
        ]);
    }

    public function confirmScript(OnboardingService $onboarding): RedirectResponse
    {
        $tenant = current_tenant();
        abort_unless($tenant?->id, 404);
        abort_unless($onboarding->isEligible($tenant), 404);

        $onboarding->markScriptConfirmed($tenant);

        return back()->with('success', 'Langkah paste script ditandai selesai.');
    }

    public function dismiss(OnboardingService $onboarding): RedirectResponse
    {
        $tenant = current_tenant();
        abort_unless($tenant?->id, 404);
        abort_unless($onboarding->isEligible($tenant), 404);

        $onboarding->dismiss($tenant);

        return redirect()
            ->to(panel_route('dashboard'))
            ->with('success', 'Panduan setup disembunyikan. Bisa dibuka lagi dari menu Mulai Setup.');
    }
}
