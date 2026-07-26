<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('telegram_bot_token')->nullable()->after('whatsapp');
            $table->string('telegram_chat_id', 64)->nullable()->after('telegram_bot_token');
            $table->string('telegram_router_session', 100)->nullable()->after('telegram_chat_id');
            $table->boolean('telegram_enabled')->default(false)->after('telegram_router_session');
            $table->unsignedBigInteger('telegram_last_update_id')->nullable()->after('telegram_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'telegram_bot_token',
                'telegram_chat_id',
                'telegram_router_session',
                'telegram_enabled',
                'telegram_last_update_id',
            ]);
        });
    }
};
