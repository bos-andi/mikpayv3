<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_pending_payments', function (Blueprint $table) {
            $table->string('purpose', 20)->default('subscription')->after('package');
        });
    }

    public function down(): void
    {
        Schema::table('platform_pending_payments', function (Blueprint $table) {
            $table->dropColumn('purpose');
        });
    }
};
