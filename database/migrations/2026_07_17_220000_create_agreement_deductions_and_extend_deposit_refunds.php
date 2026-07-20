<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreement_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agreement_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'agreement_id']);
        });

        Schema::table('deposit_refunds', function (Blueprint $table) {
            $table->decimal('gross_deposit_amount', 12, 2)->default(0)->after('amount');
            $table->decimal('deductions_amount', 12, 2)->default(0)->after('gross_deposit_amount');
            $table->decimal('debt_offset_amount', 12, 2)->default(0)->after('deductions_amount');
            $table->foreignId('debt_payment_id')
                ->nullable()
                ->after('debt_offset_amount')
                ->constrained('payments')
                ->nullOnDelete();
            $table->foreignId('refund_credit_payment_id')
                ->nullable()
                ->after('debt_payment_id')
                ->constrained('payments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('deposit_refunds', function (Blueprint $table) {
            $table->dropConstrainedForeignId('refund_credit_payment_id');
            $table->dropConstrainedForeignId('debt_payment_id');
            $table->dropColumn([
                'gross_deposit_amount',
                'deductions_amount',
                'debt_offset_amount',
            ]);
        });

        Schema::dropIfExists('agreement_deductions');
    }
};
