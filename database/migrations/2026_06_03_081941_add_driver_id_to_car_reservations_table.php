<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('car_reservations', function (Blueprint $table) {
            if (! Schema::hasColumn('car_reservations', 'driver_id')) {
                $table->foreignId('driver_id')
                    ->nullable()
                    ->after('car_id')
                    ->constrained('drivers')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('car_reservations', function (Blueprint $table) {
            if (Schema::hasColumn('car_reservations', 'driver_id')) {
                $table->dropForeign(['driver_id']);
                $table->dropColumn('driver_id');
            }
        });
    }
};
