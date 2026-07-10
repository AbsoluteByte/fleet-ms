<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('posting_status', 20)->default('pending')->after('notes');
            $table->foreignId('created_by')->nullable()->after('posting_status')->constrained('users')->nullOnDelete();
            $table->boolean('auto_allocate')->default(true)->after('created_by');
            $table->unsignedBigInteger('allocation_source_id')->nullable()->after('auto_allocate');
            $table->json('allocation_invoice_types')->nullable()->after('allocation_source_id');
            $table->json('pending_manual_allocations')->nullable()->after('allocation_invoice_types');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->string('posting_status', 20)->default('pending')->after('document');
            $table->foreignId('created_by')->nullable()->after('posting_status')->constrained('users')->nullOnDelete();
        });

        Schema::create('daily_financial_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->date('sheet_date');
            $table->string('status', 20)->default('open');
            $table->decimal('cash_in', 12, 2)->nullable();
            $table->decimal('cash_out', 12, 2)->nullable();
            $table->json('bank_in_json')->nullable();
            $table->text('approval_notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'sheet_date']);
        });

        DB::table('payments')->update(['posting_status' => 'posted']);
        DB::table('expenses')->update(['posting_status' => 'posted']);
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_financial_sheets');

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn('posting_status');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn([
                'posting_status',
                'auto_allocate',
                'allocation_source_id',
                'allocation_invoice_types',
                'pending_manual_allocations',
            ]);
        });
    }
};
