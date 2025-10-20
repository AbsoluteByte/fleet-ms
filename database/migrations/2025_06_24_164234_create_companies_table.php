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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('director_name');
            $table->string('logo')->nullable();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('postcode');
            $table->string('town');
            $table->string('county');
            $table->foreignId('country_id')->constrained()->onDelete('cascade');
            $table->string('phone');
            $table->string('email');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
