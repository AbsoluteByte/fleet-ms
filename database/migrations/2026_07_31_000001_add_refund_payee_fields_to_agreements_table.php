<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->string('refund_person_name', 255)->nullable()->after('closing_date');
            $table->string('refund_account_number', 50)->nullable()->after('refund_person_name');
        });
    }

    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropColumn(['refund_person_name', 'refund_account_number']);
        });
    }
};
