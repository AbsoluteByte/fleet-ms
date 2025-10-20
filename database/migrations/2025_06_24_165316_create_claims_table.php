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
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained()->onDelete('cascade');
            $table->foreignId('insurance_provider_id')->constrained()->onDelete('cascade');
            $table->date('case_date');
            $table->date('incident_date');
            $table->string('our_reference');
            $table->string('case_reference');
            $table->string('courtesy_type');
            $table->text('follow_up')->nullable();
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
        Schema::dropIfExists('claims');
    }
};
