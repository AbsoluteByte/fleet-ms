<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait SetupAgreementChangeCarDatabase
{
    protected function setUpAgreementChangeCarDatabase(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->nullable();
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

        Schema::create('counsels', function (Blueprint $table) {
            $table->id();
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
            $table->string('vin')->nullable();
            $table->year('manufacture_year')->nullable();
            $table->year('registration_year')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->string('purchase_type')->default('uk');
            $table->string('fleet_status')->default('available_for_rent');
            $table->boolean('sorn_applied')->default(false);
            $table->date('available_from_date')->nullable();
            $table->unsignedBigInteger('createdBy')->nullable();
            $table->unsignedBigInteger('updatedBy')->nullable();
            $table->timestamps();
        });

        Schema::create('car_mots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id');
            $table->date('expiry_date');
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('term')->default('12 months');
            $table->timestamps();
        });

        Schema::create('car_road_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id');
            $table->date('start_date');
            $table->string('term')->default('12 months');
            $table->decimal('amount', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('car_phvs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id');
            $table->foreignId('counsel_id');
            $table->decimal('amount', 10, 2)->default(0);
            $table->date('start_date');
            $table->date('expiry_date');
            $table->integer('notify_before_expiry')->default(30);
            $table->timestamps();
        });

        Schema::create('car_insurances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id');
            $table->timestamps();
        });

        Schema::create('car_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->timestamps();
        });

        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->timestamps();
        });

        Schema::create('agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('driver_id')->nullable();
            $table->foreignId('car_id')->nullable();
            $table->foreignId('status_id')->nullable();
            $table->unsignedBigInteger('upgraded_from_agreement_id')->nullable();
            $table->string('swap_reason')->nullable();
            $table->string('swap_phvl_issue_type')->nullable();
            $table->text('swap_phvl_issue_notes')->nullable();
            $table->text('swap_reason_notes')->nullable();
            $table->dateTime('start_date');
            $table->date('end_date');
            $table->date('billing_anchor_date')->nullable();
            $table->decimal('agreed_rent', 10, 2);
            $table->string('rent_interval');
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->string('collection_type')->nullable();
            $table->boolean('auto_schedule_collections')->default(false);
            $table->string('discount_type')->nullable();
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->text('discount_notes')->nullable();
            $table->boolean('using_own_insurance')->nullable()->default(false);
            $table->unsignedBigInteger('insurance_provider_id')->nullable();
            $table->string('own_insurance_provider_name')->nullable();
            $table->date('own_insurance_start_date')->nullable();
            $table->date('own_insurance_end_date')->nullable();
            $table->string('own_insurance_type')->nullable();
            $table->string('own_insurance_policy_number')->nullable();
            $table->text('own_insurance_proof_document')->nullable();
            $table->text('notes')->nullable();
            $table->date('termination_notice_date')->nullable();
            $table->date('termination_available_from_date')->nullable();
            $table->text('termination_notes')->nullable();
            $table->unsignedBigInteger('termination_recorded_by')->nullable();
            $table->dateTime('closing_date')->nullable();
            $table->unsignedBigInteger('createdBy')->nullable();
            $table->unsignedBigInteger('updatedBy')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();
            $table->foreignId('driver_id')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('invoice_type')->nullable();
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->text('discount_description')->nullable();
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_amount', 12, 2)->default(0);
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->string('posting_status', 20)->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->boolean('auto_allocate')->default(true);
            $table->unsignedBigInteger('allocation_source_id')->nullable();
            $table->json('allocation_invoice_types')->nullable();
            $table->json('pending_manual_allocations')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_no')->unique();
            $table->foreignId('driver_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->foreignId('bank_account_id')->nullable();
            $table->date('payment_date')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('posting_status', 20)->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->boolean('auto_allocate')->default(true);
            $table->unsignedBigInteger('allocation_source_id')->nullable();
            $table->json('allocation_invoice_types')->nullable();
            $table->json('pending_manual_allocations')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id');
            $table->foreignId('invoice_id');
            $table->decimal('allocated_amount', 12, 2)->default(0);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    protected function tearDownAgreementChangeCarDatabase(): void
    {
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('agreements');
        Schema::dropIfExists('statuses');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('car_reservations');
        Schema::dropIfExists('car_insurances');
        Schema::dropIfExists('car_phvs');
        Schema::dropIfExists('car_road_taxes');
        Schema::dropIfExists('car_mots');
        Schema::dropIfExists('cars');
        Schema::dropIfExists('counsels');
        Schema::dropIfExists('car_models');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tenants');
    }
}
