<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('last_name')->nullable()->change();
            $table->date('dob')->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->string('phone_number')->nullable()->change();
            $table->string('address1')->nullable()->change();
            $table->string('post_code')->nullable()->change();
            $table->string('town')->nullable()->change();
            $table->foreignId('country_id')->nullable()->change();
            $table->string('driver_license_number')->nullable()->change();
            $table->date('driver_license_expiry_date')->nullable()->change();
            $table->string('next_of_kin')->nullable()->change();
            $table->string('next_of_kin_phone')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('last_name')->nullable(false)->change();
            $table->date('dob')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
            $table->string('phone_number')->nullable(false)->change();
            $table->string('address1')->nullable(false)->change();
            $table->string('post_code')->nullable(false)->change();
            $table->string('town')->nullable(false)->change();
            $table->foreignId('country_id')->nullable(false)->change();
            $table->string('driver_license_number')->nullable(false)->change();
            $table->date('driver_license_expiry_date')->nullable(false)->change();
            $table->string('next_of_kin')->nullable(false)->change();
            $table->string('next_of_kin_phone')->nullable(false)->change();
        });
    }
};
