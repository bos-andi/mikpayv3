<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('code', 20)->nullable()->unique();
            $table->string('status', 20)->default('pending')->index();
            $table->string('phone', 30)->nullable();
            $table->string('city', 100)->nullable();
            $table->text('promotion_plan')->nullable();
            $table->decimal('first_percent', 5, 2)->nullable();
            $table->decimal('recurring_percent', 5, 2)->nullable();
            $table->unsignedInteger('clicks')->default(0);
            $table->string('bank_code', 40)->nullable();
            $table->string('bank_name', 120)->nullable();
            $table->string('account_number', 30)->nullable();
            $table->string('account_holder', 120)->nullable();
            $table->string('bank_proof_path')->nullable();
            $table->string('bank_status', 20)->nullable()->index();
            $table->string('bank_reject_reason', 500)->nullable();
            $table->timestamp('bank_reviewed_at')->nullable();
            $table->string('reject_reason', 500)->nullable();
            $table->text('admin_note')->nullable();
            $table->string('reviewed_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('referred_by_partner_id')->nullable()->after('plan')
                ->constrained('partners')->nullOnDelete();
        });

        Schema::create('partner_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tenant_name', 190);
            $table->foreignId('platform_payment_id')->nullable()->unique()
                ->constrained('platform_pending_payments')->nullOnDelete();
            $table->string('package', 40)->nullable();
            $table->string('kind', 20);
            $table->unsignedInteger('base_amount');
            $table->decimal('percent', 5, 2);
            $table->unsignedInteger('amount');
            $table->string('status', 20)->default('earned')->index();
            $table->timestamp('available_at');
            $table->string('cancel_reason', 500)->nullable();
            $table->timestamps();

            $table->index(['partner_id', 'status', 'available_at']);
        });

        Schema::create('partner_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->string('bank_code', 40);
            $table->string('bank_name', 120);
            $table->string('account_number', 30);
            $table->string('account_holder', 120);
            $table->string('note', 500)->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->string('transfer_reference', 120)->nullable();
            $table->string('admin_note', 500)->nullable();
            $table->string('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('platform_settings', function (Blueprint $table) {
            $table->json('partner_settings')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn('partner_settings');
        });
        Schema::dropIfExists('partner_withdrawals');
        Schema::dropIfExists('partner_commissions');
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by_partner_id');
        });
        Schema::dropIfExists('partners');
    }
};
