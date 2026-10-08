<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_shop_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('label', 60);
            $table->string('description', 160)->nullable();
            $table->string('profile', 128);
            $table->unsignedInteger('price');
            $table->string('limit_uptime', 32)->nullable();
            $table->unsignedBigInteger('limit_bytes')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active', 'sort']);
        });

        Schema::create('voucher_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('router_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('voucher_shop_items')->nullOnDelete();
            $table->string('reference', 24)->unique();
            $table->string('token', 48)->unique();
            $table->string('item_label', 60);
            $table->string('profile', 128);
            $table->string('limit_uptime', 32)->nullable();
            $table->unsignedBigInteger('limit_bytes')->nullable();
            $table->unsignedInteger('price');
            $table->unsignedInteger('fee')->default(0);
            $table->unsignedInteger('total');
            $table->string('phone', 32)->nullable();
            $table->string('status', 16)->default('pending');
            $table->string('voucher_code', 64)->nullable();
            $table->string('voucher_password', 64)->nullable();
            $table->string('xendit_qr_id', 100)->nullable();
            $table->string('xendit_reference_id', 100)->nullable()->unique();
            $table->text('qr_string')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->unsignedSmallInteger('fulfill_attempts')->default(0);
            $table->string('last_error', 500)->nullable();
            $table->timestamp('wa_sent_at')->nullable();
            $table->string('buyer_ip', 45)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'created_at']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_orders');
        Schema::dropIfExists('voucher_shop_items');
    }
};
