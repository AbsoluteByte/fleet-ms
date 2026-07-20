<?php

namespace Tests\Feature;

use App\Mail\CarInsuranceFleetChangeMail;
use App\Models\Car;
use App\Models\CarInsurance;
use App\Models\Company;
use App\Models\Status;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CarInsuranceFleetNotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\TestCase;

class CarInsuranceFleetEmailTest extends TestCase
{
    private Tenant $tenant;

    private Company $samoreCompany;

    private Company $proactiveCompany;

    private Company $genericCompany;

    private int $carModelId;

    private User $user;

    private int $activeInsuranceStatusId;

    private int $appliedInsuranceStatusId;

    private int $cancelledInsuranceStatusId;

    private int $providerStatusId;

    private int $samoreProviderId;

    private int $proactiveProviderId;

    private int $genericProviderId;

    private int $providerWithoutEmailId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpDatabase();

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Fleet Email Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->samoreCompany = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Samore Traders Ltd',
        ]);
        $this->proactiveCompany = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Proactive Hybrid Corporate Ltd',
        ]);
        $this->genericCompany = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Acme Motors Ltd',
        ]);

        $this->carModelId = (int) DB::table('car_models')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'name' => 'Toyota Prius',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->activeInsuranceStatusId = (int) Status::query()->create([
            'name' => 'Active',
            'type' => 'insurance',
        ])->id;
        $this->appliedInsuranceStatusId = (int) Status::query()->create([
            'name' => 'Applied',
            'type' => 'insurance',
        ])->id;
        $this->cancelledInsuranceStatusId = (int) Status::query()->create([
            'name' => 'Cancelled',
            'type' => 'insurance',
        ])->id;
        $this->providerStatusId = (int) Status::query()->create([
            'name' => 'Provider Active',
            'type' => 'insurance',
        ])->id;

        $this->samoreProviderId = $this->createProvider($this->samoreCompany->id, 'POL-SAM-001', 'insurer@samore.example.com');
        $this->proactiveProviderId = $this->createProvider($this->proactiveCompany->id, 'POL-PRO-001', 'insurer@proactive.example.com');
        $this->genericProviderId = $this->createProvider($this->genericCompany->id, 'POL-GEN-001', 'insurer@acme.example.com');
        $this->providerWithoutEmailId = $this->createProvider($this->samoreCompany->id, 'POL-NO-EMAIL', null);

        $this->user = User::factory()->create();
        $this->user->tenants()->attach($this->tenant->id, [
            'role' => 'admin',
            'is_primary' => true,
            'joined_at' => now(),
        ]);
        $this->actingAs($this->user);
        $this->user->switchTenant($this->tenant->id);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('car_status_histories');
        Schema::dropIfExists('car_services');
        Schema::dropIfExists('car_sorn_histories');
        Schema::dropIfExists('car_reservations');
        Schema::dropIfExists('car_insurances');
        Schema::dropIfExists('car_phvs');
        Schema::dropIfExists('car_road_taxes');
        Schema::dropIfExists('car_mots');
        Schema::dropIfExists('cars');
        Schema::dropIfExists('counsels');
        Schema::dropIfExists('insurance_providers');
        Schema::dropIfExists('statuses');
        Schema::dropIfExists('car_models');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');

        parent::tearDown();
    }

    public function test_create_car_with_applied_insurance_sends_add_email_to_provider_and_internal_recipient(): void
    {
        Mail::fake();

        $response = $this->post(route('cars.store'), $this->baseCreatePayload($this->samoreCompany->id, [
            'has_insurance' => 1,
            'insurance_provider_id' => $this->samoreProviderId,
            'insurance_status_id' => $this->appliedInsuranceStatusId,
            'insurance_applied_date' => now()->toDateString(),
        ]));

        $response->assertRedirect(route('cars.index'));
        Mail::assertSent(CarInsuranceFleetChangeMail::class, function (CarInsuranceFleetChangeMail $mail) {
            return $mail->action === 'add'
                && $mail->hasTo('insurer@samore.example.com')
                && $mail->hasTo(CarInsuranceFleetNotificationService::INTERNAL_RECIPIENT_EMAIL)
                && str_contains($mail->subjectLine, 'Request to Add Vehicles to Fleet Insurance Policy POL-SAM-001')
                && str_contains($mail->bodyText, 'Samore Traders Ltd')
                && str_contains($mail->bodyText, 'Toyota Prius-');
        });
    }

    public function test_update_active_to_cancelled_sends_remove_email(): void
    {
        Mail::fake();
        $car = $this->createCar($this->samoreCompany->id, 'SAM123');
        $this->createActiveInsurance($car);

        $response = $this->put(route('cars.update', $car), $this->baseUpdatePayload($car, [
            'has_insurance' => 1,
            'insurance_provider_id' => $this->samoreProviderId,
            'insurance_status_id' => $this->cancelledInsuranceStatusId,
            'insurance_canceled_date' => now()->toDateString(),
        ]));

        $response->assertRedirect(route('cars.edit', $car));
        Mail::assertSent(CarInsuranceFleetChangeMail::class, function (CarInsuranceFleetChangeMail $mail) use ($car) {
            return $mail->action === 'remove'
                && $mail->hasTo('insurer@samore.example.com')
                && $mail->hasTo(CarInsuranceFleetNotificationService::INTERNAL_RECIPIENT_EMAIL)
                && str_contains($mail->subjectLine, 'Request to Remove Vehicles from Fleet Insurance Policy POL-SAM-001 '.$car->registration)
                && str_contains($mail->bodyText, 'remove the following vehicle');
        });
    }

    public function test_resaving_applied_insurance_does_not_resend_email(): void
    {
        Mail::fake();
        $car = $this->createCar($this->samoreCompany->id, 'SAM456');
        CarInsurance::query()->create([
            'tenant_id' => $this->tenant->id,
            'car_id' => $car->id,
            'insurance_provider_id' => $this->samoreProviderId,
            'applied_date' => now()->toDateString(),
            'status_id' => $this->appliedInsuranceStatusId,
        ]);

        $response = $this->put(route('cars.update', $car), $this->baseUpdatePayload($car, [
            'has_insurance' => 1,
            'insurance_provider_id' => $this->samoreProviderId,
            'insurance_status_id' => $this->appliedInsuranceStatusId,
            'insurance_applied_date' => now()->subDay()->toDateString(),
        ]));

        $response->assertRedirect(route('cars.edit', $car));
        Mail::assertNothingSent();
    }

    public function test_provider_without_email_still_notifies_internal_recipient(): void
    {
        Mail::fake();

        $this->post(route('cars.store'), $this->baseCreatePayload($this->samoreCompany->id, [
            'has_insurance' => 1,
            'insurance_provider_id' => $this->providerWithoutEmailId,
            'insurance_status_id' => $this->appliedInsuranceStatusId,
            'insurance_applied_date' => now()->toDateString(),
        ]));

        Mail::assertSent(CarInsuranceFleetChangeMail::class, function (CarInsuranceFleetChangeMail $mail) {
            return $mail->hasTo(CarInsuranceFleetNotificationService::INTERNAL_RECIPIENT_EMAIL)
                && ! $mail->hasTo('insurer@samore.example.com');
        });
        Mail::assertSentCount(1);
    }

    public function test_proactive_company_uses_proactive_template_on_add(): void
    {
        Mail::fake();

        $this->post(route('cars.store'), $this->baseCreatePayload($this->proactiveCompany->id, [
            'has_insurance' => 1,
            'insurance_provider_id' => $this->proactiveProviderId,
            'insurance_status_id' => $this->appliedInsuranceStatusId,
            'insurance_applied_date' => now()->toDateString(),
        ]));

        Mail::assertSent(CarInsuranceFleetChangeMail::class, function (CarInsuranceFleetChangeMail $mail) {
            return str_contains($mail->bodyText, 'Proactive Hybrid Corporate Ltd')
                && str_contains($mail->bodyText, 'PROACTIVE HYBRID CORPORATE LTD');
        });
    }

    public function test_unknown_company_uses_generic_template_with_company_name(): void
    {
        Mail::fake();

        $this->post(route('cars.store'), $this->baseCreatePayload($this->genericCompany->id, [
            'has_insurance' => 1,
            'insurance_provider_id' => $this->genericProviderId,
            'insurance_status_id' => $this->appliedInsuranceStatusId,
            'insurance_applied_date' => now()->toDateString(),
        ]));

        Mail::assertSent(CarInsuranceFleetChangeMail::class, function (CarInsuranceFleetChangeMail $mail) {
            return str_contains($mail->bodyText, 'Acme Motors Ltd')
                && str_contains($mail->bodyText, 'Kindly arrange to add the following vehicles');
        });
    }

    public function test_insurance_provider_accepts_optional_email(): void
    {
        $response = $this->post(route('insurance-providers.store'), [
            'company_id' => $this->samoreCompany->id,
            'provider_name' => 'Fleet Insurer',
            'email' => 'fleet@insurer.example.com',
            'insurance_type' => 'Fleet',
            'amount' => 1200,
            'policy_number' => 'POL-NEW-001',
            'expiry_date' => now()->addYear()->toDateString(),
            'status_id' => $this->providerStatusId,
        ]);

        $response->assertRedirect(route('insurance-providers.index'));
        $this->assertDatabaseHas('insurance_providers', [
            'provider_name' => 'Fleet Insurer',
            'email' => 'fleet@insurer.example.com',
        ]);
    }

    private function createProvider(int $companyId, string $policyNumber, ?string $email): int
    {
        return (int) DB::table('insurance_providers')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'company_id' => $companyId,
            'provider_name' => 'Provider '.$policyNumber,
            'email' => $email,
            'insurance_type' => 'Fleet',
            'amount' => 1000,
            'policy_number' => $policyNumber,
            'expiry_date' => now()->addYear()->toDateString(),
            'status_id' => $this->providerStatusId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createCar(int $companyId, string $registration): Car
    {
        return Car::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $companyId,
            'car_model_id' => $this->carModelId,
            'registration' => $registration,
            'color' => 'Black',
            'vin' => 'VIN'.$registration,
            'manufacture_year' => 2020,
            'purchase_date' => now()->subYear()->toDateString(),
            'purchase_price' => 12000,
            'purchase_type' => 'uk',
            'fleet_status' => Car::FLEET_STATUS_AVAILABLE_FOR_RENT,
        ]);
    }

    private function createActiveInsurance(Car $car): CarInsurance
    {
        return CarInsurance::query()->create([
            'tenant_id' => $this->tenant->id,
            'car_id' => $car->id,
            'insurance_provider_id' => $this->samoreProviderId,
            'start_date' => now()->subMonth()->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
            'notify_before_expiry' => 30,
            'status_id' => $this->activeInsuranceStatusId,
            'insurance_document' => 'insurance.pdf',
        ]);
    }

    private function baseCreatePayload(int $companyId, array $overrides = []): array
    {
        return array_merge([
            'company_id' => $companyId,
            'car_model_id' => $this->carModelId,
            'registration' => 'REG'.uniqid(),
            'color' => 'Blue',
            'vin' => 'VIN'.uniqid(),
            'manufacture_year' => 2021,
            'purchase_date' => now()->subYear()->toDateString(),
            'purchase_price' => 15000,
            'purchase_type' => 'uk',
        ], $overrides);
    }

    private function baseUpdatePayload(Car $car, array $overrides = []): array
    {
        return array_merge([
            '_method' => 'PUT',
            'company_id' => $car->company_id,
            'car_model_id' => $car->car_model_id,
            'registration' => $car->registration,
            'color' => $car->color,
            'vin' => $car->vin,
            'manufacture_year' => $car->manufacture_year,
            'purchase_date' => $car->purchase_date->format('Y-m-d'),
            'purchase_price' => $car->purchase_price,
            'purchase_type' => $car->purchase_type,
        ], $overrides);
    }

    private function setUpDatabase(): void
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

        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('color')->nullable();
            $table->timestamps();
        });

        Schema::create('insurance_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('company_id')->nullable();
            $table->string('provider_name');
            $table->string('email')->nullable();
            $table->string('insurance_type')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('policy_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->foreignId('status_id')->nullable();
            $table->unsignedBigInteger('createdBy')->nullable();
            $table->unsignedBigInteger('updatedBy')->nullable();
            $table->timestamps();
        });

        Schema::create('counsels', function (Blueprint $table) {
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
            $table->string('registration');
            $table->string('color')->nullable();
            $table->string('vin')->nullable();
            $table->year('manufacture_year')->nullable();
            $table->year('registration_year')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->string('purchase_type')->default('uk');
            $table->string('fleet_status')->default('available_for_rent');
            $table->boolean('sorn_applied')->default(false);
            $table->timestamp('sorn_applied_at')->nullable();
            $table->unsignedBigInteger('sorn_applied_by')->nullable();
            $table->string('sorn_document')->nullable();
            $table->date('available_from_date')->nullable();
            $table->json('v5_document')->nullable();
            $table->boolean('log_book_applied')->default(false);
            $table->date('log_book_applied_date')->nullable();
            $table->unsignedBigInteger('log_book_applied_by')->nullable();
            $table->text('logbook_notes')->nullable();
            $table->json('old_log_book')->nullable();
            $table->boolean('tracker_installed')->default(false);
            $table->string('tracker_status', 20)->nullable();
            $table->text('tracker_notes')->nullable();
            $table->boolean('dashcam_installed')->default(false);
            $table->string('dashcam_status', 20)->nullable();
            $table->text('dashcam_notes')->nullable();
            $table->boolean('tag_installed')->default(false);
            $table->string('tag_status', 20)->nullable();
            $table->text('tag_notes')->nullable();
            $table->string('phv_status')->nullable();
            $table->date('phv_applied_date')->nullable();
            $table->unsignedBigInteger('phv_applied_by')->nullable();
            $table->unsignedBigInteger('createdBy')->nullable();
            $table->unsignedBigInteger('updatedBy')->nullable();
            $table->timestamps();
        });

        Schema::create('car_mots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('car_id');
            $table->date('test_date')->nullable();
            $table->date('expiry_date');
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('term')->default('12 months');
            $table->string('document')->nullable();
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
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('car_id');
            $table->foreignId('counsel_id')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->integer('notify_before_expiry')->default(30);
            $table->string('document')->nullable();
            $table->timestamps();
        });

        Schema::create('car_insurances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('car_id');
            $table->foreignId('insurance_provider_id');
            $table->date('start_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('applied_date')->nullable();
            $table->date('canceled_date')->nullable();
            $table->string('insurance_document')->nullable();
            $table->integer('notify_before_expiry')->nullable();
            $table->foreignId('status_id');
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });

        Schema::create('car_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('car_id')->nullable();
            $table->string('status')->default('active');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('car_sorn_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id');
            $table->timestamp('sorn_started_at')->nullable();
            $table->timestamp('sorn_ended_at')->nullable();
            $table->foreignId('started_by')->nullable();
            $table->foreignId('ended_by')->nullable();
            $table->timestamps();
        });

        Schema::create('car_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id');
            $table->timestamps();
        });

        Schema::create('car_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('car_id');
            $table->string('previous_status')->nullable();
            $table->string('new_status');
            $table->json('status_data')->nullable();
            $table->foreignId('changed_by')->nullable();
            $table->timestamps();
        });
    }
}
