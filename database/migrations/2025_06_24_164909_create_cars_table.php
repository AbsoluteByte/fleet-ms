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
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('car_model_id')->constrained()->onDelete('cascade');
            $table->string('registration')->unique();
            $table->string('color');
            $table->string('vin');
            $table->string('v5_document')->nullable();
            $table->year('manufacture_year');
            $table->year('registration_year');
            $table->date('purchase_date');
            $table->decimal('purchase_price', 10, 2);
            $table->enum('purchase_type', ['imported', 'uk']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
