<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->boolean('discount_is_one_time')->default(false)->after('discount_notes');
            $table->timestamp('discount_started_at')->nullable()->after('discount_is_one_time');
            $table->timestamp('discount_consumed_at')->nullable()->after('discount_started_at');
            $table->unsignedBigInteger('discount_consumed_invoice_id')->nullable()->after('discount_consumed_at');
            $table->foreign('discount_consumed_invoice_id', 'agreements_discount_invoice_fk')
                ->references('id')->on('invoices')->nullOnDelete();
        });

        DB::table('agreements')
            ->whereIn('discount_type', ['percentage', 'fixed'])
            ->where('discount_value', '>', 0)
            ->whereNull('discount_started_at')
            ->update([
                'discount_started_at' => DB::raw('COALESCE(updated_at, created_at)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropForeign('agreements_discount_invoice_fk');
            $table->dropColumn([
                'discount_is_one_time',
                'discount_started_at',
                'discount_consumed_at',
                'discount_consumed_invoice_id',
            ]);
        });
    }
};
