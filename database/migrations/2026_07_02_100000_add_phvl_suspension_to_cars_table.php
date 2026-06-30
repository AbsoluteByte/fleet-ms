<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->string('phvl_suspension_status', 32)->nullable()->after('phv_applied_by');
            $table->date('phvl_suspension_status_date')->nullable()->after('phvl_suspension_status');
        });

        Schema::create('car_phvl_suspension_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('car_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->date('event_date')->nullable();
            $table->foreignId('car_status_history_id')->nullable()->constrained('car_status_histories')->nullOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['car_id', 'created_at']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_phvl_suspension_histories');

        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn(['phvl_suspension_status', 'phvl_suspension_status_date']);
        });
    }
};
