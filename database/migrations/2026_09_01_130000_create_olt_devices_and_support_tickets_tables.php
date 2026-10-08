<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('olt_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('router_id')->constrained('routers')->cascadeOnDelete();
            $table->foreignId('odp_id')->nullable()->constrained('odps')->nullOnDelete();
            $table->string('name');
            $table->string('vendor', 32)->default('hsgq');
            $table->string('management_ip', 45)->nullable();
            $table->unsignedSmallInteger('web_port')->default(80);
            $table->unsignedTinyInteger('pon_count')->default(4);
            $table->string('status', 16)->default('unknown');
            $table->decimal('avg_rx_power', 5, 1)->nullable();
            $table->unsignedSmallInteger('onu_online_count')->default(0);
            $table->unsignedSmallInteger('onu_offline_count')->default(0);
            $table->timestamp('last_polled_at')->nullable();
            $table->text('poll_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'router_id']);
        });

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 32)->unique();
            $table->string('subject');
            $table->text('description')->nullable();
            $table->string('category', 32)->default('gangguan');
            $table->string('priority', 16)->default('normal');
            $table->string('status', 16)->default('open');
            $table->foreignId('router_id')->nullable()->constrained('routers')->nullOnDelete();
            $table->foreignId('odp_id')->nullable()->constrained('odps')->nullOnDelete();
            $table->foreignId('odp_port_id')->nullable()->constrained('odp_ports')->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('location_label')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
        });

        Schema::create('support_ticket_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();

            $table->index(['ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_updates');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('olt_devices');
    }
};
