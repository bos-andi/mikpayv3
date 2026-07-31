<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('router_id')->constrained('routers')->cascadeOnDelete();
            $table->string('type', 16)->default('odp'); // odp | olt
            $table->string('code', 64);
            $table->string('name')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedTinyInteger('port_count')->default(8);
            $table->string('pon_label', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['router_id', 'code']);
            $table->index(['tenant_id', 'router_id']);
            $table->index(['router_id', 'type']);
        });

        Schema::create('odp_ports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('odp_id')->constrained('odps')->cascadeOnDelete();
            $table->unsignedTinyInteger('port_number');
            $table->string('customer_name')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['odp_id', 'port_number']);
            $table->index('customer_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odp_ports');
        Schema::dropIfExists('odps');
    }
};
