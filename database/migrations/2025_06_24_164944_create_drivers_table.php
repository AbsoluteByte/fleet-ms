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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->date('dob');
            $table->string('email')->unique();
            $table->string('phone_number');
            $table->string('address1');
            $table->string('address2')->nullable();
            $table->string('post_code');
            $table->string('town');
            $table->string('county');
            $table->foreignId('country_id')->constrained()->onDelete('cascade');
            $table->string('driver_license_number');
            $table->date('driver_license_expiry_date');
            $table->string('phd_license_number')->nullable();
            $table->date('phd_license_expiry_date')->nullable();
            $table->string('next_of_kin');
            $table->string('next_of_kin_phone');
            $table->string('driver_license_document')->nullable();
            $table->string('driver_phd_license_document')->nullable();
            $table->string('proof_of_address_document')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
