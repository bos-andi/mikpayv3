<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->unique(['tenant_id', 'code']);
        });
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->unique('code');
        });
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'code']);
        });
    }
};
