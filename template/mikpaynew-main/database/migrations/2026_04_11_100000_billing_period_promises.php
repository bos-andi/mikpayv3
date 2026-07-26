<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_period_promises', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name', 255);
            $table->string('router_session', 100)->default('');
            $table->string('period', 7);
            $table->date('promised_pay_date');
            $table->string('promised_pay_note', 255)->nullable();
            $table->timestamps();

            $table->unique(['customer_name', 'router_session', 'period'], 'billing_period_promises_unique');
        });

        if (Schema::hasColumn('billing_customers', 'promised_pay_date')) {
            $rows = DB::table('billing_customers')
                ->whereNotNull('promised_pay_date')
                ->get(['customer_name', 'router_session', 'promised_pay_date', 'promised_pay_note']);

            foreach ($rows as $row) {
                try {
                    $d = \Carbon\Carbon::parse($row->promised_pay_date);
                } catch (\Throwable) {
                    continue;
                }
                $period = $d->format('Y-m');
                $router = (string) ($row->router_session ?? '');
                DB::table('billing_period_promises')->updateOrInsert(
                    [
                        'customer_name' => $row->customer_name,
                        'router_session' => $router,
                        'period' => $period,
                    ],
                    [
                        'promised_pay_date' => $d->toDateString(),
                        'promised_pay_note' => $row->promised_pay_note,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            Schema::table('billing_customers', function (Blueprint $table) {
                $table->dropColumn(['promised_pay_date', 'promised_pay_note']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('billing_customers', function (Blueprint $table) {
            $table->date('promised_pay_date')->nullable()->after('started_at');
            $table->string('promised_pay_note', 255)->nullable()->after('promised_pay_date');
        });

        Schema::dropIfExists('billing_period_promises');
    }
};
