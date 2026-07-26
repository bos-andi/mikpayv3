<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_ppp_connection_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('router_session', 100);
            $table->string('secret_name', 255);
            $table->string('last_state', 40); // online|offline_admin|offline_link
            $table->timestamp('last_seen_online_at')->nullable();
            $table->string('last_notified_state', 40)->nullable();
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'router_session', 'secret_name'], 'telegram_ppp_state_unique');
            $table->index(['user_id', 'router_session']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_ppp_connection_states');
    }
};
