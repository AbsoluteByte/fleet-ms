<?php

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
        Schema::table('agreement_collections', function (Blueprint $table) {
            $table->date('due_date')->after('date')->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'overdue', 'partial'])->after('amount')->default('pending');
            $table->decimal('amount_paid', 10, 2)->after('payment_status')->default(0);
            $table->date('payment_date')->after('amount_paid')->nullable();
            $table->text('notes')->after('payment_date')->nullable();
            $table->boolean('is_auto_generated')->after('notes')->default(false);

            $table->index(['due_date', 'payment_status']);
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agreement_collections', function (Blueprint $table) {
            $table->dropIndex(['due_date', 'payment_status']);
            $table->dropIndex(['payment_status']);
            $table->dropColumn([
                'due_date', 'payment_status', 'amount_paid',
                'payment_date', 'notes', 'is_auto_generated'
            ]);
        });
    }
};
