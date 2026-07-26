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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('router_id')->constrained('routers')->cascadeOnDelete();
            $table->string('customer_name'); // Username PPP (MikroTik) *
            $table->string('display_name')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('zone', 100)->nullable();
            $table->string('package_label')->nullable();
            $table->unsignedInteger('monthly_fee')->default(0);
            $table->unsignedTinyInteger('due_day')->default(0); // 0 = belum diatur
            $table->date('started_at')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['router_id', 'customer_name']);
            $table->index(['tenant_id', 'router_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_customers');
    }
};
