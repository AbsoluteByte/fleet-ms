<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_sheet_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->date('sheet_date');
            $table->string('source_type')->default('payment');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('event_type');
            $table->string('direction');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->text('description');
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'sheet_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_sheet_adjustments');
    }
};
