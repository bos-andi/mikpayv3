<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_withdrawals', function (Blueprint $table) {
            $table->string('method', 20)->nullable()->after('status');
            $table->string('xendit_external_id', 100)->nullable()->unique()->after('method');
            $table->string('xendit_disbursement_id', 100)->nullable()->index()->after('xendit_external_id');
            $table->string('xendit_status', 30)->nullable()->after('xendit_disbursement_id');
            $table->string('failure_code', 100)->nullable()->after('xendit_status');
        });
    }

    public function down(): void
    {
        Schema::table('partner_withdrawals', function (Blueprint $table) {
            $table->dropUnique(['xendit_external_id']);
            $table->dropIndex(['xendit_disbursement_id']);
            $table->dropColumn(['method', 'xendit_external_id', 'xendit_disbursement_id', 'xendit_status', 'failure_code']);
        });
    }
};
