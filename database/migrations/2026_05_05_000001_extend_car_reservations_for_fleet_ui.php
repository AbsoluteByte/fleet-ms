<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('car_reservations', function (Blueprint $table) {
            $table->dropForeign(['car_id']);
        });

        DB::statement('ALTER TABLE car_reservations MODIFY car_id BIGINT UNSIGNED NULL');

        Schema::table('car_reservations', function (Blueprint $table) {
            $table->foreign('car_id')->references('id')->on('cars')->nullOnDelete();
            $table->date('pick_up_date')->nullable()->after('reservation_date');
            $table->decimal('agreed_rent', 12, 2)->nullable()->after('pick_up_date');
            $table->decimal('agreed_advance', 12, 2)->nullable()->after('agreed_rent');
            $table->decimal('amount_paid', 12, 2)->nullable()->after('agreed_advance');
            $table->decimal('balance_payable_on_pickup', 12, 2)->nullable()->after('amount_paid');
        });
    }

    public function down(): void
    {
        Schema::table('car_reservations', function (Blueprint $table) {
            $table->dropForeign(['car_id']);
            $table->dropColumn([
                'pick_up_date',
                'agreed_rent',
                'agreed_advance',
                'amount_paid',
                'balance_payable_on_pickup',
            ]);
        });

        DB::statement('ALTER TABLE car_reservations MODIFY car_id BIGINT UNSIGNED NOT NULL');

        Schema::table('car_reservations', function (Blueprint $table) {
            $table->foreign('car_id')->references('id')->on('cars')->cascadeOnDelete();
        });
    }
};
