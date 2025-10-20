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
        Schema::create('insurance_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained()->onDelete('cascade');
            $table->foreignId('insurance_provider_id')->constrained()->onDelete('cascade');
            $table->string('policy_number')->unique();
            $table->enum('policy_type', ['comprehensive', 'third_party', 'fire_theft', 'collision']);
            $table->decimal('premium_amount', 10, 2);
            $table->decimal('excess_amount', 10, 2)->nullable();
            $table->date('policy_start_date');
            $table->date('policy_end_date');
            $table->date('next_renewal_date')->nullable();
            $table->json('coverage_details')->nullable(); // Store coverage options as JSON
            $table->enum('payment_frequency', ['monthly', 'quarterly', 'half_yearly', 'yearly']);
            $table->decimal('monthly_premium', 10, 2)->nullable();
            $table->boolean('auto_renewal')->default(false);
            $table->integer('notify_days_before_expiry')->default(30);
            $table->string('policy_document')->nullable();
            $table->enum('status', ['active', 'expired', 'cancelled', 'pending'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['policy_end_date', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_policies');
    }
};
