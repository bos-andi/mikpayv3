<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tunnel_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('username', 120)->unique();
            $table->text('password_encrypted');
            $table->string('protocol', 20)->default('openvpn'); // openvpn
            $table->string('server_host', 191);
            $table->unsignedInteger('server_port')->default(1194);
            $table->string('tunnel_ip', 45)->nullable();
            $table->string('status', 20)->default('pending'); // pending|active|disabled|expired
            $table->timestamp('connected_at')->nullable();
            $table->string('label')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('status');
        });

        Schema::create('tunnel_port_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tunnel_account_id')->constrained('tunnel_accounts')->cascadeOnDelete();
            $table->string('label', 60); // Winbox, API, WebFig, OLT
            $table->unsignedInteger('remote_port');
            $table->unsignedInteger('target_port');
            $table->string('protocol', 10)->default('tcp');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['remote_port']);
            $table->index('tunnel_account_id');
        });

        Schema::create('tunnel_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tunnel_account_id')->constrained('tunnel_accounts')->cascadeOnDelete();
            $table->string('source', 30); // mikpay_bundle|tunnel_paid
            $table->string('status', 20)->default('active'); // active|expired|cancelled
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['source', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tunnel_entitlements');
        Schema::dropIfExists('tunnel_port_maps');
        Schema::dropIfExists('tunnel_accounts');
    }
};
