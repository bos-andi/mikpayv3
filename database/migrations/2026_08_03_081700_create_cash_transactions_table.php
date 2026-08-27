<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('router_id')->constrained('routers')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('tipe', 10); // masuk|keluar
            $table->string('kategori', 50);
            $table->unsignedInteger('nominal')->default(0);
            $table->string('metode', 30)->default('tunai');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['router_id', 'tanggal']);
            $table->index(['tenant_id', 'router_id', 'tipe']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};
