<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('router_id')->constrained('routers')->cascadeOnDelete();
            $table->string('invoice_id')->nullable();
            $table->string('customer_name');
            $table->string('display_name')->nullable();
            $table->string('phone', 50)->nullable();
            $table->unsignedInteger('amount');
            $table->unsignedInteger('remaining_amount')->default(0);
            $table->string('period', 7); // YYYY-MM
            $table->dateTime('payment_date')->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('paid'); // paid|partial
            $table->timestamps();

            $table->index(['router_id', 'customer_name', 'period']);
            $table->index(['tenant_id', 'router_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_payments');
    }
};
