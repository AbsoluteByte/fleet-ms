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
        Schema::create('car_road_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->string('term');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_road_taxes');
    }
};
