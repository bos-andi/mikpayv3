<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telegram_test_last_status', 20)->nullable()->after('telegram_last_update_id');
            $table->text('telegram_test_last_message')->nullable()->after('telegram_test_last_status');
            $table->timestamp('telegram_test_last_at')->nullable()->after('telegram_test_last_message');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'telegram_test_last_status',
                'telegram_test_last_message',
                'telegram_test_last_at',
            ]);
        });
    }
};
