<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fonnte_settings', function (Blueprint $table) {
            $table->text('template_janji_bayar')->nullable()->after('template_due_today');
        });
    }

    public function down(): void
    {
        Schema::table('fonnte_settings', function (Blueprint $table) {
            $table->dropColumn('template_janji_bayar');
        });
    }
};
