<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait SetupPhvlManagementDatabase
{
    protected function setUpPhvlManagementDatabase(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('tenant_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('user_id');
            $table->string('role')->default('admin');
            $table->boolean('is_primary')->default(true);
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('car_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('company_id');
            $table->foreignId('car_model_id');
            $table->string('registration')->unique();
            $table->string('color')->nullable();
            $table->string('fleet_status')->default('preparation_for_phvl');
            $table->string('phv_status')->nullable();
            $table->string('phvl_suspension_status', 32)->nullable();
            $table->date('phvl_suspension_status_date')->nullable();
            $table->boolean('sorn_applied')->default(false);
            $table->date('available_from_date')->nullable();
            $table->unsignedBigInteger('createdBy')->nullable();
            $table->unsignedBigInteger('updatedBy')->nullable();
            $table->timestamps();
        });

        Schema::create('car_mots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id');
            $table->date('test_date')->nullable();
            $table->date('expiry_date');
            $table->timestamps();
        });

        Schema::create('car_phvs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('car_id');
            $table->foreignId('counsel_id')->nullable();
            $table->date('expiry_date')->nullable();
            $table->integer('notify_before_expiry')->nullable();
            $table->timestamps();
        });

        Schema::create('car_phvl_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('car_id');
            $table->string('mot_status', 20)->default('pending');
            $table->string('application_status', 20)->default('pending');
            $table->date('applied_date')->nullable();
            $table->string('appointment_confirmation', 20)->default('pending');
            $table->dateTime('appointment_at')->nullable();
            $table->string('phvl_result_status', 20)->nullable();
            $table->text('fail_notes')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();

            $table->unique('car_id');
        });

        Schema::create('car_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('car_id');
            $table->string('previous_status')->nullable();
            $table->string('new_status');
            $table->foreignId('reservation_id')->nullable();
            $table->foreignId('vehicle_swap_id')->nullable();
            $table->json('status_data')->nullable();
            $table->foreignId('changed_by')->nullable();
            $table->timestamps();
        });

        Schema::create('car_phvl_suspension_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('car_id');
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->date('event_date')->nullable();
            $table->foreignId('car_status_history_id')->nullable();
            $table->foreignId('changed_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('car_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('car_id')->nullable();
            $table->string('status')->default('active');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('vehicle_swaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('old_car_id')->nullable();
            $table->foreignId('swapped_with_car_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    protected function tearDownPhvlManagementDatabase(): void
    {
        Schema::dropIfExists('vehicle_swaps');
        Schema::dropIfExists('car_reservations');
        Schema::dropIfExists('car_phvl_suspension_histories');
        Schema::dropIfExists('car_status_histories');
        Schema::dropIfExists('car_phvl_progress');
        Schema::dropIfExists('car_phvs');
        Schema::dropIfExists('car_mots');
        Schema::dropIfExists('cars');
        Schema::dropIfExists('car_models');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tenants');
    }
}
