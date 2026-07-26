<?php

namespace App\Http\Controllers;

use App\Services\SubscriptionPackageService;
use Illuminate\View\View;

class LandingController extends Controller
{
    /**
     * Landing page promosi — selalu tampil di / dan /index.
     * User yang sudah login bisa lanjut ke aplikasi lewat tombol di halaman (bukan redirect otomatis).
     */
    public function index(): View
    {
        return view('index', [
            'headlines' => SubscriptionPackageService::landingHeadlines(),
            'pricingPackages' => SubscriptionPackageService::all(),
        ]);
    }
}
