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
        Schema::create('car_insurances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained()->onDelete('cascade');
            $table->foreignId('insurance_provider_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->date('expiry_date');
            $table->string('insurance_document')->nullable();
            $table->integer('notify_before_expiry');
            $table->foreignId('status_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_insurances');
    }
};
