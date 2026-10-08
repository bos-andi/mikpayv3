<?php

use App\Models\Tenant;
use App\Support\PaymentGateway;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('fee')->default(0);
            $table->unsignedBigInteger('net_amount');
            $table->string('bank_code', 40);
            $table->string('bank_name', 150)->nullable();
            $table->string('account_number', 40);
            $table->string('account_holder', 150);
            $table->text('note')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->string('method', 20)->nullable();
            $table->string('xendit_disbursement_id', 100)->nullable()->index();
            $table->string('xendit_external_id', 100)->nullable()->unique();
            $table->string('xendit_status', 30)->nullable();
            $table->string('failure_code', 100)->nullable();
            $table->string('transfer_reference', 150)->nullable();
            $table->text('admin_note')->nullable();
            $table->string('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::table('billing_payments', function (Blueprint $table) {
            $table->string('gateway', 20)->nullable()->after('payment_method')->index();
        });

        Schema::table('platform_settings', function (Blueprint $table) {
            $table->json('payout_settings')->nullable()->after('payment_channel_order');
        });

        // Pembayaran Xendit lama: tandai "platform" bila tenant memakai gateway platform MikPay.
        $platformTenantIds = Tenant::query()->get()
            ->filter(fn (Tenant $t) => PaymentGateway::active($t) === PaymentGateway::ACTIVE_PLATFORM)
            ->pluck('id')
            ->all();

        if ($platformTenantIds !== []) {
            DB::table('billing_payments')
                ->where('payment_method', 'Xendit')
                ->whereIn('tenant_id', $platformTenantIds)
                ->update(['gateway' => PaymentGateway::ACTIVE_PLATFORM]);
        }
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn('payout_settings');
        });

        Schema::table('billing_payments', function (Blueprint $table) {
            $table->dropIndex(['gateway']);
            $table->dropColumn('gateway');
        });

        Schema::dropIfExists('tenant_withdrawals');
    }
};
