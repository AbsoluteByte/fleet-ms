<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('other_payments', function (Blueprint $table) {
            $table->string('other_payment_type', 20)->default('office')->after('tenant_id');
            $table->foreignId('car_id')->nullable()->after('other_payment_type')->constrained('cars')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('other_payments', function (Blueprint $table) {
            $table->dropForeign(['car_id']);
            $table->dropColumn(['other_payment_type', 'car_id']);
        });
    }
};
