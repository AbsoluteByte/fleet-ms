<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 30);
            $table->decimal('amount', 12, 2);
            $table->date('request_date');
            $table->string('payment_method')->nullable();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->string('posting_status', 20)->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'request_date', 'posting_status'], 'driver_credit_dfs_idx');
            $table->index(['driver_id', 'posting_status']);
        });

        Schema::create('driver_credit_transaction_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_credit_transaction_id');
            $table->foreignId('source_payment_id');
            $table->foreignId('target_invoice_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('reserved');
            $table->timestamps();

            $table->foreign('driver_credit_transaction_id', 'dct_lines_transaction_fk')
                ->references('id')->on('driver_credit_transactions')->cascadeOnDelete();
            $table->foreign('source_payment_id', 'dct_lines_payment_fk')
                ->references('id')->on('payments')->restrictOnDelete();
            $table->foreign('target_invoice_id', 'dct_lines_invoice_fk')
                ->references('id')->on('invoices')->restrictOnDelete();
            $table->index(['source_payment_id', 'status']);
            $table->index(['target_invoice_id', 'status']);
        });

        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->unsignedBigInteger('driver_credit_transaction_line_id')->nullable();
            $table->unique('driver_credit_transaction_line_id', 'pa_credit_line_unique');
            $table->foreign('driver_credit_transaction_line_id', 'pa_credit_line_fk')
                ->references('id')->on('driver_credit_transaction_lines')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->dropForeign('pa_credit_line_fk');
            $table->dropUnique('pa_credit_line_unique');
            $table->dropColumn('driver_credit_transaction_line_id');
        });

        Schema::dropIfExists('driver_credit_transaction_lines');
        Schema::dropIfExists('driver_credit_transactions');
    }
};
