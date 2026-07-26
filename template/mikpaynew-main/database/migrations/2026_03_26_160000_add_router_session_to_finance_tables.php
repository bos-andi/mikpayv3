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
            $table->string('router_session', 100)->nullable()->after('status');
            $table->index(['router_session', 'payment_date'], 'billing_payments_router_payment_date_idx');
        });

        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->string('router_session', 100)->nullable()->after('keterangan');
            $table->index(['router_session', 'tanggal'], 'cash_transactions_router_tanggal_idx');
        });

        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            // Backfill pembayaran PPP berdasarkan billing_customers.customer_name.
            DB::statement("
                UPDATE billing_payments bp
                LEFT JOIN billing_customers bc ON bc.customer_name = bp.customer_name
                SET bp.router_session = bc.router_session
                WHERE bp.router_session IS NULL
            ");

            // Backfill transaksi kas yang berasal dari ppp_payment.
            DB::statement("
                UPDATE cash_transactions ct
                LEFT JOIN billing_payments bp ON bp.id = ct.source_id AND ct.source_type = 'ppp_payment'
                SET ct.router_session = bp.router_session
                WHERE ct.router_session IS NULL
            ");
        } else {
            // SQLite/testing fallback: update row-by-row.
            $customerRouterMap = DB::table('billing_customers')
                ->select(['customer_name', 'router_session'])
                ->pluck('router_session', 'customer_name');

            DB::table('billing_payments')
                ->whereNull('router_session')
                ->orderBy('id')
                ->get(['id', 'customer_name'])
                ->each(function ($row) use ($customerRouterMap) {
                    $routerSession = $customerRouterMap[$row->customer_name] ?? null;
                    DB::table('billing_payments')->where('id', $row->id)->update(['router_session' => $routerSession]);
                });

            $paymentRouterMap = DB::table('billing_payments')
                ->select(['id', 'router_session'])
                ->pluck('router_session', 'id');

            DB::table('cash_transactions')
                ->whereNull('router_session')
                ->where('source_type', 'ppp_payment')
                ->orderBy('id')
                ->get(['id', 'source_id'])
                ->each(function ($row) use ($paymentRouterMap) {
                    $routerSession = $paymentRouterMap[$row->source_id] ?? null;
                    DB::table('cash_transactions')->where('id', $row->id)->update(['router_session' => $routerSession]);
                });
        }
    }

    public function down(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->dropIndex('cash_transactions_router_tanggal_idx');
            $table->dropColumn('router_session');
        });

        Schema::table('billing_payments', function (Blueprint $table) {
            $table->dropIndex('billing_payments_router_payment_date_idx');
            $table->dropColumn('router_session');
        });
    }
};

