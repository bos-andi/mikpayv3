<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_payments', function (Blueprint $table) {
            $table->string('payment_channel')->nullable()->after('package');
            $table->text('customer_note')->nullable()->after('proof_file');
        });
    }

    public function down(): void
    {
        Schema::table('pending_payments', function (Blueprint $table) {
            $table->dropColumn(['payment_channel', 'customer_note']);
        });
    }
};

