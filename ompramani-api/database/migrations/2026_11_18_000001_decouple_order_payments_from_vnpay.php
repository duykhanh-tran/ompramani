<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            // Add generic transaction identifiers for future payment providers (e.g. SePay)
            $table->string('transaction_ref', 100)->nullable()->index()->after('status');
            $table->string('bank_code', 50)->nullable()->after('transaction_ref');
            $table->string('bank_transaction_no', 100)->nullable()->after('bank_code');

            // Make vnp_txn_ref nullable so new payment attempts (COD, SePay) don't require VNPAY ref
            $table->string('vnp_txn_ref', 100)->nullable()->change();

            // Change default provider to 'system'
            $table->string('provider', 30)->default('system')->change();
            $table->string('payment_method', 30)->default('cod')->change();
        });

        // Migrate historical VNPAY references to the generic columns
        DB::table('order_payments')->whereNotNull('vnp_txn_ref')->update([
            'transaction_ref' => DB::raw('vnp_txn_ref'),
            'bank_code' => DB::raw('vnp_bank_code'),
            'bank_transaction_no' => DB::raw('vnp_bank_tran_no'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->dropIndex(['transaction_ref']);
            $table->dropColumn(['transaction_ref', 'bank_code', 'bank_transaction_no']);
            $table->string('vnp_txn_ref', 100)->nullable(false)->change();
            $table->string('provider', 30)->default('vnpay')->change();
            $table->string('payment_method', 30)->default('vnpay')->change();
        });
    }
};
