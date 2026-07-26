<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->string('router_name')->nullable()->after('name');
            $table->string('domain')->nullable()->after('router_name');
            $table->string('currency', 10)->default('Rp')->after('domain');
            $table->unsignedSmallInteger('expiry_days')->default(10)->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->dropColumn(['router_name', 'domain', 'currency', 'expiry_days']);
        });
    }
};
