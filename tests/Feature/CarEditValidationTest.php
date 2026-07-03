<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarInsurance;
use App\Models\CarMot;
use App\Models\Company;
use App\Models\InsuranceProvider;
use App\Models\Status;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\TestCase;

class CarEditValidationTest extends TestCase
{
    private Tenant $tenant;

    private Company $company;

    private int $carModelId;

    private User $user;

    private int $activeInsuranceStatusId;

    private int $appliedInsuranceStatusId;

    private int $cancelledInsuranceStatusId;

    private int $insuranceProviderId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpCarEditDatabase();

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Car Edit Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->company = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Car Edit Company',
        ]);

        $this->carModelId = (int) DB::table('car_models')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Model',
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

        $providerStatusId = (int) Status::query()->create([
            'name' => 'Provider Active',
            'type' => 'insurance_provider',
        ])->id;

        $this->insuranceProviderId = (int) DB::table('insurance_providers')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'provider_name' => 'Test Insurer',
            'insurance_type' => 'comprehensive',
            'amount' => 1000,
            'policy_number' => 'POL-001',
            'expiry_date' => now()->addYear()->toDateString(),
            'status_id' => $providerStatusId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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
        Schema::dropIfExists('car_insurances');
        Schema::dropIfExists('insurance_providers');
        Schema::dropIfExists('car_mots');
        Schema::dropIfExists('car_phvs');
        Schema::dropIfExists('car_road_taxes');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('car_services');
        Schema::dropIfExists('car_sorn_histories');
        Schema::dropIfExists('car_status_histories');
        Schema::dropIfExists('car_reservations');
        Schema::dropIfExists('cars');
        Schema::dropIfExists('car_models');
        Schema::dropIfExists('statuses');
        Schema::dropIfExists('counsels');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tenants');

        parent::tearDown();
    }

    public function test_update_succeeds_when_historical_mot_has_null_test_date(): void
    {
        $car = $this->createCar();
        $latestMot = $this->createMot($car, [
            'test_date' => now()->subYear()->toDateString(),
            'expiry_date' => now()->addMonths(6)->toDateString(),
            'document' => 'latest.pdf',
        ]);
        $olderMot = $this->createMot($car, [
            'test_date' => null,
            'expiry_date' => now()->subYear()->toDateString(),
            'document' => 'older.pdf',
        ]);

        $response = $this->from(route('cars.edit', $car))
            ->put(route('cars.update', $car), $this->baseUpdatePayload($car, [
            'color' => 'Blue',
            'mots' => [
                0 => [
                    'id' => $latestMot->id,
                    'test_date' => $latestMot->test_date->format('Y-m-d'),
                    'expiry_date' => $latestMot->expiry_date->format('Y-m-d'),
                    'amount' => $latestMot->amount,
                    'term' => $latestMot->term,
                ],
                1 => [
                    'id' => $olderMot->id,
                    'test_date' => '',
                    'expiry_date' => $olderMot->expiry_date->format('Y-m-d'),
                    'amount' => $olderMot->amount,
                    'term' => $olderMot->term,
                ],
            ],
        ]));

        $response->assertRedirect(route('cars.edit', $car));
        $response->assertSessionDoesntHaveErrors();
        $this->assertNull(session('error'), (string) session('error'));

        $olderMot->refresh();
        $this->assertNull($olderMot->test_date);
        $this->assertSame('Blue', $car->fresh()->color);
    }

    public function test_validation_error_keeps_historical_mots_in_preserved_section(): void
    {
        $car = $this->createCar();
        $latestMot = $this->createMot($car, [
            'test_date' => now()->subYear()->toDateString(),
            'expiry_date' => now()->addMonths(6)->toDateString(),
            'document' => 'latest.pdf',
        ]);
        $olderMot = $this->createMot($car, [
            'test_date' => null,
            'expiry_date' => now()->subYear()->toDateString(),
            'document' => 'older.pdf',
        ]);

        $this->createActiveInsurance($car);

        $response = $this->from(route('cars.edit', $car))
            ->put(route('cars.update', $car), $this->baseUpdatePayload($car, [
                'has_insurance' => 1,
                'insurance_provider_id' => $this->insuranceProviderId,
                'insurance_status_id' => $this->appliedInsuranceStatusId,
                'insurance_applied_date' => now()->toDateString(),
                'mots' => [
                    0 => [
                        'id' => $latestMot->id,
                        'test_date' => $latestMot->test_date->format('Y-m-d'),
                        'expiry_date' => $latestMot->expiry_date->format('Y-m-d'),
                        'amount' => $latestMot->amount,
                        'term' => $latestMot->term,
                    ],
                    1 => [
                        'id' => $olderMot->id,
                        'test_date' => '',
                        'expiry_date' => $olderMot->expiry_date->format('Y-m-d'),
                        'amount' => $olderMot->amount,
                        'term' => $olderMot->term,
                    ],
                ],
            ]));

        $response->assertRedirect(route('cars.edit', $car));
        $response->assertSessionHasErrors('insurance_status_id');

        $editResponse = $this->get(route('cars.edit', $car));
        $editResponse->assertOk();
        $content = $editResponse->getContent();
        $this->assertStringContainsString('id="mots-preserved"', $content);
        $this->assertStringContainsString('class="mot-preserved"', $content);
        $this->assertStringContainsString('name="mots[1][id]"', $content);
        $this->assertStringContainsString('value="'.$olderMot->id.'"', $content);
        preg_match('/id="mots-container"(.*?)id="mots-preserved"/s', $content, $matches);
        $mainMotSection = $matches[1] ?? '';
        $this->assertEquals(1, substr_count($mainMotSection, 'class="mot-item row'));
        $this->assertStringNotContainsString('name="mots[1][test_date]"', $mainMotSection);
    }

    public function test_update_blocks_active_to_applied_insurance_status(): void
    {
        $car = $this->createCar();
        $this->createActiveInsurance($car);

        $response = $this->from(route('cars.edit', $car))
            ->put(route('cars.update', $car), $this->baseUpdatePayload($car, [
                'has_insurance' => 1,
                'insurance_provider_id' => $this->insuranceProviderId,
                'insurance_status_id' => $this->appliedInsuranceStatusId,
                'insurance_applied_date' => now()->toDateString(),
            ]));

        $response->assertRedirect(route('cars.edit', $car));
        $response->assertSessionHasErrors('insurance_status_id');
    }

    public function test_update_blocks_cancelled_insurance_without_document_on_file(): void
    {
        $car = $this->createCar();
        $this->createActiveInsurance($car, withDocument: false);

        $response = $this->from(route('cars.edit', $car))
            ->put(route('cars.update', $car), $this->baseUpdatePayload($car, [
                'has_insurance' => 1,
                'insurance_provider_id' => $this->insuranceProviderId,
                'insurance_status_id' => $this->cancelledInsuranceStatusId,
                'insurance_canceled_date' => now()->toDateString(),
            ]));

        $response->assertRedirect(route('cars.edit', $car));
        $response->assertSessionHasErrors('insurance_document');
    }

    private function createCar(): Car
    {
        return Car::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'car_model_id' => $this->carModelId,
            'registration' => 'EDIT'.uniqid(),
            'color' => 'Red',
            'vin' => 'VIN'.uniqid(),
            'manufacture_year' => 2020,
            'purchase_date' => now()->subYear()->toDateString(),
            'purchase_price' => 12000,
            'purchase_type' => 'uk',
            'fleet_status' => Car::FLEET_STATUS_AVAILABLE_FOR_RENT,
        ]);
    }

    private function createMot(Car $car, array $overrides = []): CarMot
    {
        return CarMot::query()->create(array_merge([
            'tenant_id' => $this->tenant->id,
            'car_id' => $car->id,
            'test_date' => now()->subMonths(6)->toDateString(),
            'expiry_date' => now()->addMonths(6)->toDateString(),
            'amount' => 45,
            'term' => '12 months',
            'document' => null,
        ], $overrides));
    }

    private function createActiveInsurance(Car $car, bool $withDocument = true): CarInsurance
    {
        return CarInsurance::query()->create([
            'tenant_id' => $this->tenant->id,
            'car_id' => $car->id,
            'insurance_provider_id' => $this->insuranceProviderId,
            'start_date' => now()->subMonth()->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
            'notify_before_expiry' => 30,
            'status_id' => $this->activeInsuranceStatusId,
            'insurance_document' => $withDocument ? 'insurance.pdf' : null,
        ]);
    }

    private function baseUpdatePayload(Car $car, array $overrides = []): array
    {
        return array_merge([
            '_method' => 'PUT',
            'company_id' => $this->company->id,
            'car_model_id' => $this->carModelId,
            'registration' => $car->registration,
            'color' => $car->color,
            'vin' => $car->vin,
            'manufacture_year' => $car->manufacture_year,
            'purchase_date' => $car->purchase_date->format('Y-m-d'),
            'purchase_price' => $car->purchase_price,
            'purchase_type' => $car->purchase_type,
        ], $overrides);
    }

    private function setUpCarEditDatabase(): void
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
            $table->string('insurance_type')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('policy_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->foreignId('status_id')->nullable();
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
