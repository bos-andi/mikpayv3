<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_period_promises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('router_id')->constrained('routers')->cascadeOnDelete();
            $table->string('customer_name');
            $table->string('period', 7); // Y-m
            $table->date('promised_pay_date');
            $table->string('promised_pay_note', 255)->nullable();
            $table->timestamps();

            $table->unique(['router_id', 'customer_name', 'period'], 'billing_period_promises_unique');
            $table->index(['tenant_id', 'router_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_period_promises');
    }
};
