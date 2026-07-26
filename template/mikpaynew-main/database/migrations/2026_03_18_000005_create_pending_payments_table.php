<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_id')->unique(); // PAY + YmdHis + rand
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('package');
            $table->unsignedInteger('amount');
            $table->string('proof_file')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->dateTime('approved_at')->nullable();
            $table->string('approved_by')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->string('rejected_by')->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_payments');
    }
};
