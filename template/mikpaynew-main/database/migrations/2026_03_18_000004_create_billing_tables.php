<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name')->unique();
            $table->unsignedTinyInteger('due_day')->default(10);
            $table->string('display_name')->nullable();
            $table->string('phone')->nullable();
            $table->unsignedInteger('monthly_fee')->default(0);
            $table->text('notes')->nullable();
            $table->string('router_session')->nullable();
            $table->timestamps();
        });

        Schema::create('billing_payments', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_id')->nullable();
            $table->string('customer_name');
            $table->string('display_name')->nullable();
            $table->string('phone')->nullable();
            $table->unsignedInteger('amount');
            $table->string('period', 7); // 2026-01
            $table->dateTime('payment_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('paid'); // paid, unpaid
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_payments');
        Schema::dropIfExists('billing_customers');
    }
};
