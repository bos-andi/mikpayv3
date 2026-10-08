<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tenants')->where('status', 'suspended')->update(['status' => 'inactive']);
    }

    public function down(): void
    {
        // Legacy rollback only — prefer inactive going forward.
        DB::table('tenants')->where('status', 'inactive')->update(['status' => 'suspended']);
    }
};
