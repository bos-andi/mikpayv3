<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tunnel_nodes', function (Blueprint $table) {
            $table->unsignedInteger('last_allocated_port')->nullable()->after('port_range_end');
        });

        $maxByNode = DB::table('tunnel_port_maps')
            ->join('tunnel_accounts', 'tunnel_accounts.id', '=', 'tunnel_port_maps.tunnel_account_id')
            ->groupBy('tunnel_accounts.tunnel_node_id')
            ->selectRaw('tunnel_accounts.tunnel_node_id as node_id, MAX(tunnel_port_maps.remote_port) as max_port')
            ->pluck('max_port', 'node_id');

        foreach ($maxByNode as $nodeId => $maxPort) {
            DB::table('tunnel_nodes')->where('id', $nodeId)->update(['last_allocated_port' => (int) $maxPort]);
        }
    }

    public function down(): void
    {
        Schema::table('tunnel_nodes', function (Blueprint $table) {
            $table->dropColumn('last_allocated_port');
        });
    }
};
