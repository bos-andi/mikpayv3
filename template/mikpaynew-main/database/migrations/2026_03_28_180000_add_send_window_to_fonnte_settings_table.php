<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fonnte_settings', function (Blueprint $table) {
            $table->string('send_window_start', 5)->default('08:00')->after('auto_send_overdue');
            $table->string('send_window_end', 5)->default('20:00')->after('send_window_start');
        });
    }

    public function down(): void
    {
        Schema::table('fonnte_settings', function (Blueprint $table) {
            $table->dropColumn(['send_window_start', 'send_window_end']);
        });
    }
};
