<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fonnte_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->string('api_token')->nullable();
            $table->string('sender_name')->nullable();
            $table->boolean('auto_send_h3')->default(false);
            $table->boolean('auto_send_h0')->default(false);
            $table->boolean('auto_send_overdue')->default(false);
            $table->boolean('antispam_enabled')->default(true);
            $table->unsignedSmallInteger('antispam_delay_min')->default(15);
            $table->unsignedSmallInteger('antispam_delay_max')->default(30);
            $table->unsignedSmallInteger('antispam_hourly_limit')->default(10);
            $table->unsignedSmallInteger('antispam_daily_limit')->default(50);
            $table->boolean('antispam_randomize_message')->default(true);
            $table->json('template_greetings')->nullable();
            $table->text('template_reminder')->nullable();
            $table->text('template_due_today')->nullable();
            $table->text('template_overdue')->nullable();
            $table->json('template_closings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fonnte_settings');
    }
};
