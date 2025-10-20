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
        Schema::create('car_phvs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained()->onDelete('cascade');
            $table->foreignId('counsel_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->date('start_date');
            $table->date('expiry_date');
            $table->integer('notify_before_expiry');
            $table->string('document')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_phvs');
    }
};
