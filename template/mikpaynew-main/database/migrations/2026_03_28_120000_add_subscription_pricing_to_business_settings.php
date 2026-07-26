<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->json('subscription_premium_features')->nullable()->after('payment_channel_order');
            $table->json('subscription_packages_meta')->nullable()->after('subscription_premium_features');
            $table->json('subscription_landing_headlines')->nullable()->after('subscription_packages_meta');
        });
    }

    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_premium_features',
                'subscription_packages_meta',
                'subscription_landing_headlines',
            ]);
        });
    }
};
