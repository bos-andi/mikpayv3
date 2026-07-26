<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_grace_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('router_session', 100)->nullable();
            $table->string('customer_name', 255);
            $table->date('grace_until');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'router_session']);
            $table->unique(['user_id', 'router_session', 'customer_name'], 'telegram_grace_unique_customer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_grace_overrides');
    }
};
