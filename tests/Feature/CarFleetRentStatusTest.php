<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Car;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Status;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CarFleetRentStatusService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\Concerns\SetupAgreementChangeCarDatabase;
use Tests\TestCase;

class CarFleetRentStatusTest extends TestCase
{
    use SetupAgreementChangeCarDatabase;

    private Tenant $tenant;

    private Company $company;

    private Driver $driver;

    private Car $car;

    private Car $secondCar;

    private Status $activeStatus;

    private Status $replacementStatus;

    private Status $terminatedStatus;

    private CarFleetRentStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpAgreementChangeCarDatabase();
        $this->setUpHttpTestExtras();
        $this->setUpAgreementStoreExtras();

        Carbon::setTestNow(Carbon::parse('2026-06-18 10:00:00'));

        $this->tenant = Tenant::create(['company_name' => 'Rent Status Tenant']);
        $this->company = Company::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Rent Status Company',
        ]);

        $this->driver = Driver::create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Rent',
            'last_name' => 'Driver',
            'email' => 'rent-driver@example.com',
            'phone_number' => '07000000001',
            'dob' => '1990-01-01',
            'address1' => '1 Test Street',
            'post_code' => 'SW1A 1AA',
            'town' => 'London',
            'country_id' => 1,
            'driver_license_number' => 'DL123456',
            'driver_license_expiry_date' => '2027-01-01',
            'next_of_kin' => 'Jane Driver',
            'next_of_kin_phone' => '07000000002',
        ]);

        $carModelId = DB::table('car_models')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $counselId = DB::table('counsels')->insertGetId([
            'name' => 'Test Counsel',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->car = $this->createCompliantCar('CAR111', $carModelId, $counselId);
        $this->secondCar = $this->createCompliantCar('CAR222', $carModelId, $counselId);

        $this->activeStatus = Status::create(['name' => 'Active', 'type' => 'agreement']);
        $this->replacementStatus = Status::create(['name' => 'Replacement Vehicle', 'type' => 'agreement']);
        $this->terminatedStatus = Status::create(['name' => 'Terminated', 'type' => 'agreement']);

        $user = User::factory()->create();
        DB::table('model_has_roles')->insert([
            'role_id' => (int) DB::table('roles')->value('id'),
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);
        $user->tenants()->attach($this->tenant->id, [
            'role' => 'admin',
            'is_primary' => true,
            'joined_at' => now(),
        ]);
        $this->actingAs($user);
        $user->switchTenant($this->tenant->id);

        $this->service = app(CarFleetRentStatusService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('agreement_collections');
        Schema::dropIfExists('car_status_histories');
        Schema::dropIfExists('countries');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('tenant_user');
        $this->tearDownAgreementChangeCarDatabase();

        parent::tearDown();
    }

    public function test_active_agreement_marks_car_on_rent_with_future_start_date(): void
    {
        $agreement = $this->createAgreement($this->car, $this->activeStatus, [
            'start_date' => Carbon::parse('2026-07-01 09:00:00'),
        ]);

        $this->service->syncForAgreement($agreement);

        $this->assertSame(Car::FLEET_STATUS_ON_RENT, $this->car->fresh()->fleet_status);
    }

    public function test_replacement_vehicle_agreement_marks_car_on_rent(): void
    {
        $parent = $this->createAgreement($this->secondCar, $this->activeStatus);
        $replacement = Agreement::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $this->car->id,
            'parent_agreement_id' => $parent->id,
            'start_date' => Carbon::parse('2026-06-17'),
            'end_date' => Carbon::parse('2027-06-17'),
            'agreed_rent' => 0,
            'rent_interval' => 'weekly',
            'deposit_amount' => 0,
            'collection_type' => 'weekly',
            'status_id' => $this->replacementStatus->id,
            'createdBy' => 1,
            'updatedBy' => 1,
        ]);

        $this->service->syncForAgreement($replacement);

        $this->assertSame(Car::FLEET_STATUS_ON_RENT, $this->car->fresh()->fleet_status);
    }

    public function test_terminating_agreement_releases_compliant_car_to_available_for_rent(): void
    {
        $agreement = $this->createAgreement($this->car, $this->activeStatus);
        $this->service->syncForAgreement($agreement);
        $this->assertSame(Car::FLEET_STATUS_ON_RENT, $this->car->fresh()->fleet_status);

        $agreement->update([
            'status_id' => $this->terminatedStatus->id,
            'termination_notice_date' => '2026-06-18',
            'termination_available_from_date' => '2026-06-20',
        ]);

        $this->service->syncForCar($this->car->fresh());

        $this->assertSame(Car::FLEET_STATUS_AVAILABLE_FOR_RENT, $this->car->fresh()->fleet_status);
    }

    public function test_terminating_agreement_releases_non_compliant_car(): void
    {
        DB::table('car_mots')->where('car_id', $this->car->id)->update([
            'expiry_date' => '2026-01-01',
        ]);
        $this->car = $this->car->fresh(['mots', 'roadTaxes', 'phvs']);

        $agreement = $this->createAgreement($this->car, $this->activeStatus);
        $this->service->syncForAgreement($agreement);
        $this->assertSame(Car::FLEET_STATUS_ON_RENT, $this->car->fresh()->fleet_status);

        $agreement->update([
            'status_id' => $this->terminatedStatus->id,
            'termination_notice_date' => '2026-06-18',
            'termination_available_from_date' => '2026-06-20',
        ]);

        $this->service->syncForCar($this->car->fresh(['mots', 'roadTaxes', 'phvs']));

        $this->assertSame(Car::FLEET_STATUS_NON_COMPLIANT, $this->car->fresh()->fleet_status);
    }

    public function test_cron_sync_marks_and_releases_cars(): void
    {
        $agreement = $this->createAgreement($this->car, $this->activeStatus);
        $this->car->update(['fleet_status' => Car::FLEET_STATUS_AVAILABLE_FOR_RENT]);
        $this->secondCar->update(['fleet_status' => Car::FLEET_STATUS_ON_RENT]);

        Artisan::call('cars:sync-fleet-rent-status');

        $this->assertSame(Car::FLEET_STATUS_ON_RENT, $this->car->fresh()->fleet_status);
        $this->assertSame(Car::FLEET_STATUS_AVAILABLE_FOR_RENT, $this->secondCar->fresh()->fleet_status);
        $this->assertStringContainsString('marked on rent', Artisan::output());
    }

    public function test_on_rent_car_is_not_selectable_for_new_agreement(): void
    {
        $agreement = $this->createAgreement($this->car, $this->activeStatus);
        $this->service->syncForAgreement($agreement);

        $rentedCarIds = Agreement::rentedCarIdsForTenant($this->tenant->id);

        $this->assertFalse($this->car->fresh()->isSelectableForAgreement($rentedCarIds));
    }

    public function test_creating_active_agreement_via_store_marks_car_on_rent(): void
    {
        $response = $this->from(route('agreements.create'))
            ->post(route('agreements.store'), [
                'company_id' => $this->company->id,
                'driver_id' => $this->driver->id,
                'car_id' => $this->car->id,
                'start_date' => '2026-06-18T09:00',
                'end_date' => '2027-06-17',
                'agreed_rent' => 200,
                'rent_interval' => 'Weekly',
                'collection_type' => 'weekly',
                'deposit_amount' => 500,
                'status_id' => $this->activeStatus->id,
            ]);

        $response->assertRedirect(route('agreements.index'));
        $response->assertSessionHasNoErrors();
        $this->assertSame(Car::FLEET_STATUS_ON_RENT, $this->car->fresh()->fleet_status);
    }

    public function test_can_update_agreement_with_same_on_rent_car(): void
    {
        $agreement = $this->createAgreement($this->car, $this->activeStatus);
        $this->service->syncForAgreement($agreement);

        $this->assertSame(Car::FLEET_STATUS_ON_RENT, $this->car->fresh()->fleet_status);

        $response = $this->from(route('agreements.edit', $agreement))
            ->put(route('agreements.update', $agreement), $this->updatePayload([
                'agreed_rent' => 250,
            ]));

        $response->assertRedirect(route('agreements.index'));
        $response->assertSessionHasNoErrors();
        $this->assertEquals(250, (float) $agreement->fresh()->agreed_rent);
    }

    public function test_cannot_update_agreement_to_different_on_rent_car(): void
    {
        $firstAgreement = $this->createAgreement($this->car, $this->activeStatus);
        $this->service->syncForAgreement($firstAgreement);

        $secondAgreement = $this->createAgreement($this->secondCar, $this->activeStatus);
        $this->service->syncForAgreement($secondAgreement);

        $this->assertSame(Car::FLEET_STATUS_ON_RENT, $this->secondCar->fresh()->fleet_status);

        $response = $this->from(route('agreements.edit', $firstAgreement))
            ->put(route('agreements.update', $firstAgreement), $this->updatePayload([
                'car_id' => $this->secondCar->id,
            ]));

        $response->assertSessionHasErrors('car_id');
    }

    private function createAgreement(Car $car, Status $status, array $overrides = []): Agreement
    {
        return Agreement::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $car->id,
            'start_date' => Carbon::parse('2026-06-17'),
            'end_date' => Carbon::parse('2027-06-17'),
            'agreed_rent' => 200,
            'rent_interval' => 'weekly',
            'deposit_amount' => 500,
            'collection_type' => 'weekly',
            'using_own_insurance' => false,
            'status_id' => $status->id,
            'createdBy' => 1,
            'updatedBy' => 1,
        ], $overrides));
    }

    private function updatePayload(array $overrides = []): array
    {
        return array_merge([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $this->car->id,
            'start_date' => '2026-06-17T09:00',
            'end_date' => '2027-06-17',
            'agreed_rent' => 200,
            'rent_interval' => 'Weekly',
            'collection_type' => 'weekly',
            'deposit_amount' => 500,
            'status_id' => $this->activeStatus->id,
        ], $overrides);
    }

    private function createCompliantCar(string $registration, int $carModelId, int $counselId): Car
    {
        $car = Car::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'car_model_id' => $carModelId,
            'registration' => $registration,
            'color' => 'Black',
            'vin' => uniqid('VIN', true),
            'manufacture_year' => 2020,
            'registration_year' => 2020,
            'purchase_date' => '2020-01-01',
            'purchase_price' => 10000,
            'purchase_type' => 'uk',
            'fleet_status' => Car::FLEET_STATUS_AVAILABLE_FOR_RENT,
            'sorn_applied' => false,
        ]);

        DB::table('car_mots')->insert([
            'car_id' => $car->id,
            'expiry_date' => '2027-01-01',
            'amount' => 50,
            'term' => '12 months',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('car_road_taxes')->insert([
            'car_id' => $car->id,
            'start_date' => '2026-01-01',
            'term' => '12 months',
            'amount' => 200,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('car_phvs')->insert([
            'car_id' => $car->id,
            'counsel_id' => $counselId,
            'amount' => 100,
            'start_date' => '2026-01-01',
            'expiry_date' => '2027-01-01',
            'notify_before_expiry' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $car;
    }

    private function setUpHttpTestExtras(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->default(1);
        });

        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        DB::table('countries')->insert([
            'id' => 1,
            'name' => 'United Kingdom',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('drivers', function (Blueprint $table) {
            $table->date('dob')->nullable();
            $table->string('address1')->nullable();
            $table->string('post_code')->nullable();
            $table->string('town')->nullable();
            $table->foreignId('country_id')->nullable();
            $table->string('driver_license_number')->nullable();
            $table->date('driver_license_expiry_date')->nullable();
            $table->string('next_of_kin')->nullable();
            $table->string('next_of_kin_phone')->nullable();
            $table->boolean('is_active')->default(true);
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

        DB::table('roles')->insert([
            'id' => 1,
            'name' => 'admin',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('car_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('car_id');
            $table->string('previous_status')->nullable();
            $table->string('new_status');
            $table->json('status_data')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamps();
        });
    }

    private function setUpAgreementStoreExtras(): void
    {
        if (! Schema::hasColumn('agreements', 'mutual_detail_slip_document')) {
            Schema::table('agreements', function (Blueprint $table) {
                $table->json('mutual_detail_slip_document')->nullable();
            });
        }

        if (! Schema::hasColumn('agreements', 'parent_agreement_id')) {
            Schema::table('agreements', function (Blueprint $table) {
                $table->unsignedBigInteger('parent_agreement_id')->nullable();
            });
        }

        if (! Schema::hasTable('agreement_collections')) {
            Schema::create('agreement_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id');
            $table->date('date');
            $table->date('due_date')->nullable();
            $table->string('method');
            $table->decimal('amount', 10, 2);
            $table->string('payment_status')->default('pending');
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_auto_generated')->default(false);
            $table->timestamps();
            });
        }

        if (! Schema::hasTable('bank_accounts')) {
            Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('company_id');
            $table->string('bank_name');
            $table->string('account_number', 50);
            $table->unsignedBigInteger('createdBy')->nullable();
            $table->unsignedBigInteger('updatedBy')->nullable();
            $table->timestamps();
            });
        }
    }
}
