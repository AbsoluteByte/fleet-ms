<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('car_reservations', function (Blueprint $table) {
            $table->string('posting_status', 20)->nullable()->after('bank_account_id');
            $table->unsignedBigInteger('converted_agreement_id')->nullable()->after('posting_status');
        });
    }

    public function down(): void
    {
        Schema::table('car_reservations', function (Blueprint $table) {
            $table->dropColumn(['posting_status', 'converted_agreement_id']);
        });
    }
};
