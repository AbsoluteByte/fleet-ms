<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_sorn_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('car_id')->constrained('cars')->cascadeOnDelete();
            $table->timestamp('sorn_started_at');
            $table->foreignId('sorn_started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sorn_ended_at')->nullable();
            $table->foreignId('sorn_ended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sorn_document')->nullable();
            $table->timestamps();

            $table->index(['car_id', 'sorn_started_at']);
            $table->index(['tenant_id', 'car_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_sorn_histories');
    }
};
