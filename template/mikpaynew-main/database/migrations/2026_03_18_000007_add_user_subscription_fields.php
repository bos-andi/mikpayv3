<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('subscription_package')->nullable()->after('status');
            $table->string('subscription_status')->nullable()->after('subscription_package');
            $table->date('subscription_start')->nullable()->after('subscription_status');
            $table->date('subscription_end')->nullable()->after('subscription_start');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_package',
                'subscription_status',
                'subscription_start',
                'subscription_end',
            ]);
        });
    }
};

