<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_payments', function (Blueprint $table) {
            $table->unsignedInteger('remaining_amount')->default(0)->after('amount');
            $table->index(['router_session', 'customer_name', 'period'], 'billing_payments_router_customer_period_idx');
        });

        DB::table('billing_payments')->update(['remaining_amount' => 0]);
    }

    public function down(): void
    {
        Schema::table('billing_payments', function (Blueprint $table) {
            $table->dropIndex('billing_payments_router_customer_period_idx');
            $table->dropColumn('remaining_amount');
        });
    }
};
