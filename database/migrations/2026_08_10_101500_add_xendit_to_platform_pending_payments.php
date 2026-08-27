<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_pending_payments', function (Blueprint $table) {
            $table->string('xendit_invoice_id')->nullable()->after('proof_file');
            $table->string('xendit_external_id')->nullable()->after('xendit_invoice_id');
            $table->string('xendit_invoice_url', 500)->nullable()->after('xendit_external_id');
            $table->index('xendit_external_id');
        });
    }

    public function down(): void
    {
        Schema::table('platform_pending_payments', function (Blueprint $table) {
            $table->dropIndex(['xendit_external_id']);
            $table->dropColumn(['xendit_invoice_id', 'xendit_external_id', 'xendit_invoice_url']);
        });
    }
};
