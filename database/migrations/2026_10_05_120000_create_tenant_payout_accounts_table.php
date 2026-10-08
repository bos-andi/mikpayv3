<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_payout_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('bank_code', 40);
            $table->string('bank_name', 150)->nullable();
            $table->string('account_number', 40);
            $table->string('account_holder', 150);
            $table->string('proof_path');
            $table->string('status', 20)->default('pending')->index();
            $table->text('reject_reason')->nullable();
            $table->string('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'id']);
        });

        Schema::table('tenant_withdrawals', function (Blueprint $table) {
            $table->foreignId('payout_account_id')->nullable()->after('user_id')
                ->constrained('tenant_payout_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tenant_withdrawals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payout_account_id');
        });

        Schema::dropIfExists('tenant_payout_accounts');
    }
};
