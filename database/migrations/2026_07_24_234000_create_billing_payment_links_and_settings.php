<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_customers', function (Blueprint $table) {
            $table->string('public_code', 16)->nullable()->after('customer_name');
            $table->unique(['tenant_id', 'public_code']);
        });

        Schema::table('billing_payments', function (Blueprint $table) {
            $table->string('xendit_invoice_id', 64)->nullable()->after('invoice_id');
            $table->string('xendit_external_id', 120)->nullable()->after('xendit_invoice_id');
            $table->unsignedBigInteger('payment_link_id')->nullable()->after('xendit_external_id');
            $table->index('xendit_invoice_id');
            $table->index('xendit_external_id');
        });

        Schema::create('billing_payment_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('router_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('customer_name');
            $table->string('display_name')->nullable();
            $table->string('phone', 50)->nullable();
            $table->json('periods');
            $table->unsignedInteger('amount');
            $table->string('status', 20)->default('pending'); // pending|paid|expired|cancelled
            $table->string('xendit_invoice_id', 64)->nullable();
            $table->string('xendit_external_id', 120)->nullable();
            $table->string('xendit_invoice_url', 500)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('wa_sent_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'customer_name']);
            $table->index(['router_id', 'status']);
            $table->index('xendit_external_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_payment_links');

        Schema::table('billing_payments', function (Blueprint $table) {
            $table->dropIndex(['xendit_invoice_id']);
            $table->dropIndex(['xendit_external_id']);
            $table->dropColumn(['xendit_invoice_id', 'xendit_external_id', 'payment_link_id']);
        });

        Schema::table('billing_customers', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'public_code']);
            $table->dropColumn('public_code');
        });
    }
};
