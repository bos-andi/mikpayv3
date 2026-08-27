<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('router_id')->unique()->constrained('routers')->cascadeOnDelete();
            $table->unsignedBigInteger('opening_cash')->default(0);
            $table->unsignedBigInteger('opening_equity')->default(0);
            $table->date('opening_as_of')->nullable();
            $table->timestamps();
        });

        Schema::create('finance_debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('router_id')->constrained('routers')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('keterangan', 255);
            $table->unsignedInteger('nominal')->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['router_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_debts');
        Schema::dropIfExists('finance_settings');
    }
};
