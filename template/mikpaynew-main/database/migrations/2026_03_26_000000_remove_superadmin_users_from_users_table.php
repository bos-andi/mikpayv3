<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Super Admin hanya lewat .env (session), bukan baris users.
     */
    public function up(): void
    {
        DB::table('users')
            ->where(function ($q) {
                $q->where('is_super_admin', true)
                    ->orWhere('role', 'super_admin');
            })
            ->delete();
    }

    public function down(): void
    {
        // Tidak mengembalikan data user yang dihapus.
    }
};
