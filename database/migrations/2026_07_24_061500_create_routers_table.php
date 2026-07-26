<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('host');
            $table->unsignedSmallInteger('port')->default(8728);
            $table->string('username');
            $table->text('password');
            $table->boolean('use_ssl')->default(false);
            $table->string('status')->default('enable'); // enable|disable
            $table->boolean('is_default')->default(false);
            $table->string('isolir_profile')->nullable();
            $table->string('active_profile')->nullable();
            $table->boolean('last_check_ok')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->string('last_check_message')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routers');
    }
};
