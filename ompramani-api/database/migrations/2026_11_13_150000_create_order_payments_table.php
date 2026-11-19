<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            // Restrict on delete to protect financial audit history
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->unsignedInteger('attempt_number')->default(1);
            $table->string('payment_method', 30)->default('vnpay');
            $table->string('provider', 30)->default('vnpay');
            $table->unsignedBigInteger('amount_vnd');
            $table->string('currency', 10)->default('VND');
            $table->string('status', 30)->default('pending');

            // VNPAY specific transaction identifiers
            $table->string('vnp_txn_ref', 100)->unique();
            $table->string('vnp_transaction_no', 100)->nullable()->index();
            $table->string('vnp_bank_code', 50)->nullable();
            $table->string('vnp_bank_tran_no', 100)->nullable();
            $table->string('vnp_card_type', 50)->nullable();
            $table->text('vnp_order_info')->nullable();
            $table->string('vnp_pay_date', 20)->nullable();
            $table->string('vnp_response_code', 10)->nullable();
            $table->string('vnp_transaction_status', 10)->nullable();
            $table->string('vnp_secure_hash', 255)->nullable();

            // Audit and verification
            $table->json('raw_payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['order_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_payments');
    }
};
