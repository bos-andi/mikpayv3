<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_customers', function (Blueprint $table) {
            $table->text('address')->nullable();
            $table->string('zone', 100)->nullable();
            $table->string('package_label', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->date('started_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('billing_customers', function (Blueprint $table) {
            $table->dropColumn(['address', 'zone', 'package_label', 'email', 'started_at']);
        });
    }
};
