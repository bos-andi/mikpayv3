<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_customers', function (Blueprint $table) {
            $table->date('promised_pay_date')->nullable()->after('started_at');
            $table->string('promised_pay_note', 255)->nullable()->after('promised_pay_date');
        });
    }

    public function down(): void
    {
        Schema::table('billing_customers', function (Blueprint $table) {
            $table->dropColumn(['promised_pay_date', 'promised_pay_note']);
        });
    }
};

