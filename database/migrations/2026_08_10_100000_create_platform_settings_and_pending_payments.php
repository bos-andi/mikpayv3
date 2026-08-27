<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->json('landing_content')->nullable();
            $table->json('subscription_packages_meta')->nullable();
            $table->json('subscription_landing_headlines')->nullable();
            $table->json('payment_channel_order')->nullable();
            $table->string('subscription_verification_whatsapp', 40)->nullable();
            $table->timestamps();
        });

        Schema::create('platform_pending_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_id', 40)->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('package', 40);
            $table->string('payment_channel', 40)->nullable();
            $table->unsignedBigInteger('amount')->default(0);
            $table->string('proof_file')->nullable();
            $table->text('customer_note')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejected_by')->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_pending_payments');
        Schema::dropIfExists('platform_settings');
    }
};
