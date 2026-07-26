<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tunnel_nodes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique(); // an-1, cgk-2
            $table->string('hostname', 191)->unique(); // an-1.mikpay.link
            $table->string('label')->nullable();
            $table->string('region', 60)->nullable();
            $table->unsignedInteger('ovpn_port')->default(1194);
            $table->unsignedInteger('port_range_start')->default(1000);
            $table->unsignedInteger('port_range_end')->default(3000);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('tunnel_accounts', function (Blueprint $table) {
            $table->foreignId('tunnel_node_id')->nullable()->after('user_id')->constrained('tunnel_nodes')->nullOnDelete();
        });

        $base = (string) config('mikpay.tenant_base_domain', 'mikpay.link');
        $nodeId = DB::table('tunnel_nodes')->insertGetId([
            'code' => 'an-1',
            'hostname' => 'an-1.'.$base,
            'label' => 'Node AN-1',
            'region' => 'ID',
            'ovpn_port' => (int) config('mikpay.tunnel.server_port', 1194),
            'port_range_start' => (int) config('mikpay.tunnel.port_range_start', 1000),
            'port_range_end' => (int) config('mikpay.tunnel.port_range_end', 3000),
            'is_active' => true,
            'is_default' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tunnel_accounts')->update([
            'tunnel_node_id' => $nodeId,
            'server_host' => 'an-1.'.$base,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('tunnel_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tunnel_node_id');
        });
        Schema::dropIfExists('tunnel_nodes');
    }
};
