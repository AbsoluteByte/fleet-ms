<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposit_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agreement_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method');
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->date('refund_date');
            $table->string('posting_status', 20)->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('daily_financial_sheets', function (Blueprint $table) {
            $table->json('bank_out_json')->nullable()->after('bank_in_json');
        });
    }

    public function down(): void
    {
        Schema::table('daily_financial_sheets', function (Blueprint $table) {
            $table->dropColumn('bank_out_json');
        });

        Schema::dropIfExists('deposit_refunds');
    }
};
