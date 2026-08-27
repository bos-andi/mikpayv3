<?php

namespace App\Http\Controllers;

use App\Services\LandingContentService;
use App\Services\SubscriptionPackageService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('landing.index', [
            'landing' => LandingContentService::all(),
            'pricingPackages' => SubscriptionPackageService::all(),
            'pricingHeadlines' => SubscriptionPackageService::landingHeadlines(),
        ]);
    }
}
