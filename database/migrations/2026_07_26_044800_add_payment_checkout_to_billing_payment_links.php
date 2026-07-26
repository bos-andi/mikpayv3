<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_payment_links', function (Blueprint $table) {
            $table->json('payment_checkout')->nullable()->after('xendit_invoice_url');
        });
    }

    public function down(): void
    {
        Schema::table('billing_payment_links', function (Blueprint $table) {
            $table->dropColumn('payment_checkout');
        });
    }
};
