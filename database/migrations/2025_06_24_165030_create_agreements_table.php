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
        Schema::create('agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignId('driver_id')->constrained()->onDelete('cascade');
            $table->foreignId('car_id')->constrained()->onDelete('cascade');
            $table->decimal('agreed_rent', 10, 2);
            $table->string('rent_interval');
            $table->decimal('deposit_amount', 10, 2);
            $table->text('condition_report')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('status_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agreements');
    }
};
