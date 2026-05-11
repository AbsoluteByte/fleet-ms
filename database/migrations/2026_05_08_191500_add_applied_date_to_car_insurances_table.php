<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('car_insurances', function (Blueprint $table) {
            if (! Schema::hasColumn('car_insurances', 'applied_date')) {
                $table->date('applied_date')->nullable()->after('expiry_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('car_insurances', function (Blueprint $table) {
            if (Schema::hasColumn('car_insurances', 'applied_date')) {
                $table->dropColumn('applied_date');
            }
        });
    }
};
