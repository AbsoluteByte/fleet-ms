<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_swaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('old_car_id')->constrained('cars')->cascadeOnDelete();
            $table->foreignId('swapped_with_car_id')->constrained('cars')->cascadeOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->date('reservation_date');
            $table->date('pick_up_date');
            $table->decimal('agreed_rent', 12, 2);
            $table->decimal('agreed_advance', 12, 2);
            $table->decimal('amount_paid', 12, 2);
            $table->decimal('balance_payable_on_pickup', 12, 2);
            $table->string('reason_for_swap');
            $table->string('phvl_issue_type')->nullable();
            $table->text('phvl_issue_notes')->nullable();
            $table->text('reason_notes')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_swaps');
    }
};
