<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fonnte_reminder_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('customer_name', 191);
            $table->string('kind', 16);
            $table->string('dispatch_date', 10);
            $table->timestamps();

            $table->unique(['user_id', 'customer_name', 'kind', 'dispatch_date'], 'fonnte_dispatch_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fonnte_reminder_dispatches');
    }
};
