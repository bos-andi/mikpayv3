<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->string('source', 16)->default('panel')->after('status');
            $table->foreignId('billing_customer_id')->nullable()->after('odp_port_id')->constrained('billing_customers')->nullOnDelete();
            $table->string('reporter_phone', 32)->nullable()->after('location_label');
            $table->string('public_token', 40)->nullable()->unique()->after('code');

            $table->index(['tenant_id', 'billing_customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'billing_customer_id', 'status']);
            $table->dropConstrainedForeignId('billing_customer_id');
            $table->dropUnique(['public_token']);
            $table->dropColumn(['source', 'reporter_phone', 'public_token']);
        });
    }
};
