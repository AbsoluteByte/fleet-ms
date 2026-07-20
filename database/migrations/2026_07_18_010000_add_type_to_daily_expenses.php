<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('daily_expense_type', 20)->nullable()->after('type');
        });

        DB::table('expenses')
            ->where('type', 'Daily')
            ->whereNull('daily_expense_type')
            ->update(['daily_expense_type' => 'office']);
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('daily_expense_type');
        });
    }
};
