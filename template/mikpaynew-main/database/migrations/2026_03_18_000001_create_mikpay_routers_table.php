<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mikpay_routers', function (Blueprint $table) {
            $table->id();
            $table->string('session_name')->unique();
            $table->string('host'); // IP:PORT
            $table->string('username');
            $table->text('password'); // base64 or encrypted
            $table->string('router_name')->nullable();
            $table->string('domain')->nullable();
            $table->string('currency', 10)->default('Rp');
            $table->unsignedTinyInteger('currency_position')->default(10); // 10=front, 20=back
            $table->unsignedTinyInteger('expiry_mode')->default(1); // 1=remove, 2=notify
            $table->unsignedSmallInteger('expiry_days')->default(10);
            $table->string('status')->default('enable'); // enable, disable
            $table->string('livereport')->default('disable')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mikpay_routers');
    }
};
