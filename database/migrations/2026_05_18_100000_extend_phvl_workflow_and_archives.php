<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('car_phvl_progress', function (Blueprint $table) {
            $table->text('appointment_notes')->nullable()->after('appointment_confirmation');
        });

        DB::table('car_phvl_progress')
            ->where('appointment_confirmation', 'confirmed')
            ->update(['appointment_confirmation' => 'approved']);

        Schema::create('car_phvl_archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('car_id')->constrained()->cascadeOnDelete();
            $table->foreignId('car_phv_id')->nullable()->constrained('car_phvs')->nullOnDelete();
            $table->string('mot_status', 20)->default('pending');
            $table->string('application_status', 20)->default('pending');
            $table->date('applied_date')->nullable();
            $table->string('appointment_confirmation', 30)->default('pending');
            $table->text('appointment_notes')->nullable();
            $table->dateTime('appointment_at')->nullable();
            $table->string('phvl_result_status', 20)->nullable();
            $table->text('fail_notes')->nullable();
            $table->string('renewal_context')->nullable();
            $table->json('phv_summary')->nullable();
            $table->timestamp('completed_at');
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'car_id']);
        });

        Schema::create('car_phvl_progress_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('car_id')->constrained()->cascadeOnDelete();
            $table->foreignId('archive_id')->nullable()->constrained('car_phvl_archives')->nullOnDelete();
            $table->string('field', 64);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['car_id', 'archive_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_phvl_progress_events');
        Schema::dropIfExists('car_phvl_archives');

        Schema::table('car_phvl_progress', function (Blueprint $table) {
            $table->dropColumn('appointment_notes');
        });

        DB::table('car_phvl_progress')
            ->where('appointment_confirmation', 'approved')
            ->update(['appointment_confirmation' => 'confirmed']);
    }
};
