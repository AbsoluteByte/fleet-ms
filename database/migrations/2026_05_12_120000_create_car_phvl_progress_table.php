<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_phvl_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('car_id')->constrained()->cascadeOnDelete();
            $table->string('mot_status', 20)->default('pending');
            $table->string('application_status', 20)->default('pending');
            $table->date('applied_date')->nullable();
            $table->string('appointment_confirmation', 20)->default('pending');
            $table->dateTime('appointment_at')->nullable();
            $table->string('phvl_result_status', 20)->nullable();
            $table->text('fail_notes')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('car_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_phvl_progress');
    }
};
