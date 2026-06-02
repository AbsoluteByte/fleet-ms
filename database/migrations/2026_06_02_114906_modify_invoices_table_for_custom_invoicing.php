<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropConstrainedForeignId('subscription_id');

            // Remove legacy invoice columns that are no longer needed.
            $table->dropColumn([
                'stripe_invoice_id',
                'invoice_number',
                'amount',
                'tax',
                'total',
                'paid_at',
                'pdf_path',
            ]);

            // New flexible invoice schema.
            $table->string('invoice_no')->unique()->after('id');
            $table->foreignId('driver_id')->nullable()->after('invoice_no')->constrained()->nullOnDelete();
            $table->unsignedBigInteger('source_id')->nullable()->after('driver_id');
            $table->string('invoice_type')->nullable()->after('source_id');
            $table->date('invoice_date')->nullable()->after('invoice_type');
            $table->decimal('subtotal', 12, 2)->default(0)->after('due_date');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('subtotal');
            $table->text('discount_description')->nullable()->after('discount_amount');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('discount_description');
            $table->decimal('total_amount', 12, 2)->default(0)->after('tax_amount');
            $table->decimal('paid_amount', 12, 2)->default(0)->after('total_amount');
            $table->decimal('balance_amount', 12, 2)->default(0)->after('paid_amount');
            $table->text('notes')->nullable()->after('status');

            $table->index(['invoice_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('driver_id');
            $table->dropIndex(['invoice_type', 'source_id']);
            $table->dropColumn([
                'invoice_no',
                'source_id',
                'invoice_type',
                'invoice_date',
                'subtotal',
                'discount_amount',
                'discount_description',
                'tax_amount',
                'total_amount',
                'paid_amount',
                'balance_amount',
                'notes',
            ]);

            $table->foreignId('tenant_id')->after('id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->after('tenant_id')->constrained()->onDelete('cascade');
            $table->string('stripe_invoice_id')->nullable()->unique()->after('subscription_id');
            $table->string('invoice_number')->unique()->after('stripe_invoice_id');
            $table->decimal('amount', 10, 2)->after('invoice_number');
            $table->decimal('tax', 10, 2)->default(0)->after('amount');
            $table->decimal('total', 10, 2)->after('tax');
            $table->timestamp('paid_at')->nullable()->after('status');
            $table->string('pdf_path')->nullable()->after('due_date');
        });
    }
};
