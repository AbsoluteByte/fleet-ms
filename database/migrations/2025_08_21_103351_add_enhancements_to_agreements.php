<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->integer('mileage_out')->after('security_deposit')->nullable();
            $table->integer('mileage_in')->after('mileage_out')->nullable();
            $table->enum('collection_type', ['weekly', 'monthly', 'static'])->after('rent_interval')->default('monthly');
            $table->boolean('auto_schedule_collections')->after('collection_type')->default(true);
            $table->date('next_collection_date')->after('auto_schedule_collections')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropColumn([
                'security_deposit', 'mileage_out', 'mileage_in',
                'collection_type', 'auto_schedule_collections', 'next_collection_date'
            ]);
        });
    }
};
