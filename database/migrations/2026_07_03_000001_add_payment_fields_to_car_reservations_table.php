<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('car_reservations', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('amount_paid');
            $table->foreignId('bank_account_id')->nullable()->after('payment_method')
                ->constrained('bank_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('car_reservations', function (Blueprint $table) {
            $table->dropForeign(['bank_account_id']);
            $table->dropColumn(['payment_method', 'bank_account_id']);
        });
    }
};
