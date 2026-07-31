<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waapi_reminder_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('router_id')->constrained('routers')->cascadeOnDelete();
            $table->string('customer_name');
            $table->string('kind', 32); // h3|h0|overdue
            $table->date('dispatch_date');
            $table->string('phone', 40)->nullable();
            $table->timestamps();

            $table->unique(
                ['router_id', 'customer_name', 'kind', 'dispatch_date'],
                'waapi_reminder_dispatches_unique'
            );
            $table->index(['tenant_id', 'dispatch_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waapi_reminder_dispatches');
    }
};
