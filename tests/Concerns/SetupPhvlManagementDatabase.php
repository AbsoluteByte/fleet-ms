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
    }

    protected function tearDownPhvlManagementDatabase(): void
    {
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
