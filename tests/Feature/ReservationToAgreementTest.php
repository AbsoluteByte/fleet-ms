<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Car;
use App\Models\CarReservation;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Status;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\Concerns\SetupAgreementChangeCarDatabase;
use Tests\TestCase;

class ReservationToAgreementTest extends TestCase
{
    use SetupAgreementChangeCarDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpAgreementChangeCarDatabase();
        $this->setUpHttpTestExtras();

        Carbon::setTestNow(Carbon::parse('2026-06-25 10:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('agreement_collections');
        Schema::dropIfExists('car_status_histories');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('countries');
        $this->tearDownAgreementChangeCarDatabase();

        parent::tearDown();
    }

    public function test_can_create_agreement_from_reservation_when_car_is_reserved(): void
    {
        $tenant = Tenant::query()->create([
            'company_name' => 'Reservation Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $company = Company::query()->create(['tenant_id' => $tenant->id, 'name' => 'Co']);
        $driver = Driver::query()->create($this->agreementReadyDriverAttributes($tenant->id, [
            'first_name' => 'Ali',
            'last_name' => 'Khan',
            'email' => 'ali@example.com',
            'phone_number' => '07000000003',
            'driver_license_number' => 'LIC000001',
        ]));

        $carModelId = DB::table('car_models')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => 'Test Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $counselId = DB::table('counsels')->insertGetId([
            'name' => 'Test Counsel',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $car = Car::query()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'car_model_id' => $carModelId,
            'registration' => 'RSV001',
            'color' => 'Black',
            'vin' => 'VINRSV001',
            'manufacture_year' => 2020,
            'registration_year' => 2020,
            'purchase_date' => '2020-01-01',
            'purchase_price' => 10000,
            'purchase_type' => 'uk',
            'fleet_status' => 'reserved',
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

        $activeStatus = Status::query()->create(['name' => 'Active', 'type' => 'agreement']);

        $user = User::factory()->create();
        $user->tenants()->attach($tenant->id, [
            'role' => 'admin',
            'is_primary' => true,
            'joined_at' => now(),
        ]);
        $this->actingAs($user);
        $user->switchTenant($tenant->id);

        $reservation = CarReservation::query()->create([
            'tenant_id' => $tenant->id,
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'customer_name' => 'Ali Khan',
            'reservation_date' => '2026-06-25',
            'pick_up_date' => '2026-06-30',
            'agreed_rent' => 150,
            'agreed_advance' => 200,
            'amount_paid' => 50,
            'balance_payable_on_pickup' => 300,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $response = $this->from(route('agreements.create'))
            ->post(route('agreements.store'), [
            'reservation_id' => $reservation->id,
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'car_id' => $car->id,
            'start_date' => '2026-06-30T09:00',
            'end_date' => '2027-06-30',
            'agreed_rent' => 150,
            'rent_interval' => 'Weekly',
            'collection_type' => 'weekly',
            'deposit_amount' => 200,
            'status_id' => $activeStatus->id,
        ]);

        $response->assertRedirect(route('agreements.index'));
        $response->assertSessionHasNoErrors();

        $this->assertSoftDeleted('car_reservations', ['id' => $reservation->id]);
        $this->assertDatabaseHas('agreements', [
            'tenant_id' => $tenant->id,
            'driver_id' => $driver->id,
            'car_id' => $car->id,
        ]);
    }

    public function test_can_create_agreement_when_reservation_car_not_yet_saved_to_database(): void
    {
        $tenant = Tenant::query()->create([
            'company_name' => 'Reservation Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $company = Company::query()->create(['tenant_id' => $tenant->id, 'name' => 'Co']);
        $driver = Driver::query()->create($this->agreementReadyDriverAttributes($tenant->id, [
            'first_name' => 'Sara',
            'last_name' => 'Ali',
            'email' => 'sara@example.com',
            'phone_number' => '07000000004',
            'driver_license_number' => 'LIC000002',
        ]));

        $carModelId = DB::table('car_models')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => 'Test Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $counselId = DB::table('counsels')->insertGetId([
            'name' => 'Test Counsel',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $car = Car::query()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'car_model_id' => $carModelId,
            'registration' => 'RSV002',
            'color' => 'Black',
            'vin' => 'VINRSV002',
            'manufacture_year' => 2020,
            'registration_year' => 2020,
            'purchase_date' => '2020-01-01',
            'purchase_price' => 10000,
            'purchase_type' => 'uk',
            'fleet_status' => 'reserved',
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

        $activeStatus = Status::query()->create(['name' => 'Active', 'type' => 'agreement']);

        $user = User::factory()->create();
        $user->tenants()->attach($tenant->id, [
            'role' => 'admin',
            'is_primary' => true,
            'joined_at' => now(),
        ]);
        $this->actingAs($user);
        $user->switchTenant($tenant->id);

        $reservation = CarReservation::query()->create([
            'tenant_id' => $tenant->id,
            'car_id' => null,
            'driver_id' => $driver->id,
            'customer_name' => 'Sara Ali',
            'reservation_date' => '2026-06-25',
            'pick_up_date' => '2026-06-30',
            'agreed_rent' => 150,
            'agreed_advance' => 200,
            'amount_paid' => 50,
            'balance_payable_on_pickup' => 300,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $response = $this->from(route('agreements.create'))
            ->post(route('agreements.store'), [
            'reservation_id' => $reservation->id,
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'car_id' => $car->id,
            'start_date' => '2026-06-30T09:00',
            'end_date' => '2027-06-30',
            'agreed_rent' => 150,
            'rent_interval' => 'Weekly',
            'collection_type' => 'weekly',
            'deposit_amount' => 200,
            'status_id' => $activeStatus->id,
        ]);

        $response->assertRedirect(route('agreements.index'));
        $response->assertSessionHasNoErrors();
    }

    public function test_can_create_agreement_from_reserved_non_compliant_car(): void
    {
        $tenant = Tenant::query()->create([
            'company_name' => 'Reservation Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $company = Company::query()->create(['tenant_id' => $tenant->id, 'name' => 'Co']);
        $driver = Driver::query()->create($this->agreementReadyDriverAttributes($tenant->id, [
            'first_name' => 'Omar',
            'last_name' => 'Hassan',
            'email' => 'omar@example.com',
            'phone_number' => '07000000005',
            'driver_license_number' => 'LIC000003',
        ]));

        $carModelId = DB::table('car_models')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => 'Test Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $counselId = DB::table('counsels')->insertGetId([
            'name' => 'Test Counsel',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $car = Car::query()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'car_model_id' => $carModelId,
            'registration' => 'RSV003',
            'color' => 'Black',
            'vin' => 'VINRSV003',
            'manufacture_year' => 2020,
            'registration_year' => 2020,
            'purchase_date' => '2020-01-01',
            'purchase_price' => 10000,
            'purchase_type' => 'uk',
            'fleet_status' => Car::FLEET_STATUS_NON_COMPLIANT,
            'sorn_applied' => false,
        ]);

        DB::table('car_mots')->insert([
            'car_id' => $car->id,
            'expiry_date' => '2026-01-01',
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

        $activeStatus = Status::query()->create(['name' => 'Active', 'type' => 'agreement']);

        $user = User::factory()->create();
        $user->tenants()->attach($tenant->id, [
            'role' => 'admin',
            'is_primary' => true,
            'joined_at' => now(),
        ]);
        $this->actingAs($user);
        $user->switchTenant($tenant->id);

        $reservation = CarReservation::query()->create([
            'tenant_id' => $tenant->id,
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'customer_name' => 'Omar Hassan',
            'reservation_date' => '2026-06-25',
            'pick_up_date' => '2026-06-30',
            'agreed_rent' => 150,
            'agreed_advance' => 200,
            'amount_paid' => 50,
            'balance_payable_on_pickup' => 300,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $response = $this->from(route('agreements.create'))
            ->post(route('agreements.store'), [
            'reservation_id' => $reservation->id,
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'car_id' => $car->id,
            'start_date' => '2026-06-30T09:00',
            'end_date' => '2027-06-30',
            'agreed_rent' => 150,
            'rent_interval' => 'Weekly',
            'collection_type' => 'weekly',
            'deposit_amount' => 200,
            'status_id' => $activeStatus->id,
        ]);

        $response->assertRedirect(route('agreements.index'));
        $response->assertSessionHasNoErrors();
    }

    public function test_can_create_agreement_using_active_reservation_fallback_without_reservation_id(): void
    {
        $tenant = Tenant::query()->create([
            'company_name' => 'Reservation Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $company = Company::query()->create(['tenant_id' => $tenant->id, 'name' => 'Co']);
        $driver = Driver::query()->create($this->agreementReadyDriverAttributes($tenant->id, [
            'first_name' => 'Noor',
            'last_name' => 'Ali',
            'email' => 'noor@example.com',
            'phone_number' => '07000000006',
            'driver_license_number' => 'LIC000004',
        ]));

        $carModelId = DB::table('car_models')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => 'Test Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $car = Car::query()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'car_model_id' => $carModelId,
            'registration' => 'RSV004',
            'color' => 'Black',
            'vin' => 'VINRSV004',
            'manufacture_year' => 2020,
            'registration_year' => 2020,
            'purchase_date' => '2020-01-01',
            'purchase_price' => 10000,
            'purchase_type' => 'uk',
            'fleet_status' => 'reserved',
            'sorn_applied' => false,
        ]);

        $activeStatus = Status::query()->create(['name' => 'Active', 'type' => 'agreement']);

        $user = User::factory()->create();
        $user->tenants()->attach($tenant->id, [
            'role' => 'admin',
            'is_primary' => true,
            'joined_at' => now(),
        ]);
        $this->actingAs($user);
        $user->switchTenant($tenant->id);

        CarReservation::query()->create([
            'tenant_id' => $tenant->id,
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'customer_name' => 'Noor Ali',
            'reservation_date' => '2026-06-25',
            'pick_up_date' => '2026-06-30',
            'agreed_rent' => 150,
            'agreed_advance' => 200,
            'amount_paid' => 50,
            'balance_payable_on_pickup' => 300,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $response = $this->from(route('agreements.create'))
            ->post(route('agreements.store'), [
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'car_id' => $car->id,
            'start_date' => '2026-06-30T09:00',
            'end_date' => '2027-06-30',
            'agreed_rent' => 150,
            'rent_interval' => 'Weekly',
            'collection_type' => 'weekly',
            'deposit_amount' => 200,
            'status_id' => $activeStatus->id,
        ]);

        $response->assertRedirect(route('agreements.index'));
        $response->assertSessionHasNoErrors();
    }

    public function test_reservation_create_excludes_cars_on_active_rent(): void
    {
        $tenant = Tenant::query()->create([
            'company_name' => 'Reservation Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $company = Company::query()->create(['tenant_id' => $tenant->id, 'name' => 'Co']);
        $driver = Driver::query()->create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Ali',
            'last_name' => 'Khan',
            'email' => 'ali@example.com',
            'phone_number' => '07000000003',
        ]);

        $carModelId = DB::table('car_models')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => 'Test Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $counselId = DB::table('counsels')->insertGetId([
            'name' => 'Test Counsel',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $availableCar = Car::query()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'car_model_id' => $carModelId,
            'registration' => 'AVAIL001',
            'color' => 'Black',
            'vin' => 'VINAVAIL001',
            'manufacture_year' => 2020,
            'registration_year' => 2020,
            'purchase_date' => '2020-01-01',
            'purchase_price' => 10000,
            'purchase_type' => 'uk',
            'fleet_status' => 'available_for_rent',
            'sorn_applied' => false,
        ]);

        $rentedCar = Car::query()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'car_model_id' => $carModelId,
            'registration' => 'RENT001',
            'color' => 'White',
            'vin' => 'VINRENT001',
            'manufacture_year' => 2020,
            'registration_year' => 2020,
            'purchase_date' => '2020-01-01',
            'purchase_price' => 10000,
            'purchase_type' => 'uk',
            'fleet_status' => 'available_for_rent',
            'sorn_applied' => false,
        ]);

        foreach ([$availableCar, $rentedCar] as $car) {
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
        }

        $activeStatus = Status::query()->create(['name' => 'Active', 'type' => 'agreement']);

        Agreement::query()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'car_id' => $rentedCar->id,
            'status_id' => $activeStatus->id,
            'start_date' => '2026-01-01 09:00:00',
            'end_date' => '2027-06-30',
            'agreed_rent' => 150,
            'rent_interval' => 'Weekly',
            'collection_type' => 'weekly',
            'deposit_amount' => 200,
        ]);

        $selectableCars = Car::forAgreementFormSelection($tenant->id);
        $this->assertTrue($selectableCars->contains('id', $availableCar->id));
        $this->assertFalse($selectableCars->contains('id', $rentedCar->id));
    }

    public function test_agreement_from_reservation_blocked_when_driver_incomplete(): void
    {
        $tenant = Tenant::query()->create([
            'company_name' => 'Reservation Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $company = Company::query()->create(['tenant_id' => $tenant->id, 'name' => 'Co']);
        $driver = Driver::query()->create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Minimal',
        ]);

        $carModelId = DB::table('car_models')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => 'Test Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $car = Car::query()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'car_model_id' => $carModelId,
            'registration' => 'RSVINCOMPLETE',
            'color' => 'Black',
            'vin' => 'VININCOMPLETE',
            'manufacture_year' => 2020,
            'registration_year' => 2020,
            'purchase_date' => '2020-01-01',
            'purchase_price' => 10000,
            'purchase_type' => 'uk',
            'fleet_status' => 'reserved',
            'sorn_applied' => false,
        ]);

        $activeStatus = Status::query()->create(['name' => 'Active', 'type' => 'agreement']);

        $user = User::factory()->create();
        $user->tenants()->attach($tenant->id, [
            'role' => 'admin',
            'is_primary' => true,
            'joined_at' => now(),
        ]);
        $this->actingAs($user);
        $user->switchTenant($tenant->id);

        $reservation = CarReservation::query()->create([
            'tenant_id' => $tenant->id,
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'customer_name' => 'Minimal',
            'reservation_date' => '2026-06-25',
            'pick_up_date' => '2026-06-30',
            'agreed_rent' => 150,
            'agreed_advance' => 200,
            'amount_paid' => 50,
            'balance_payable_on_pickup' => 300,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $response = $this->from(route('agreements.create'))
            ->post(route('agreements.store'), [
                'reservation_id' => $reservation->id,
                'company_id' => $company->id,
                'driver_id' => $driver->id,
                'car_id' => $car->id,
                'start_date' => '2026-06-30T09:00',
                'end_date' => '2027-06-30',
                'agreed_rent' => 150,
                'rent_interval' => 'Weekly',
                'collection_type' => 'weekly',
                'deposit_amount' => 200,
                'status_id' => $activeStatus->id,
            ]);

        $response->assertRedirect(route('agreements.create'));
        $response->assertSessionHasErrors('driver_id');
        $this->assertDatabaseCount('agreements', 0);
    }

    public function test_agreement_store_applies_reservation_payment_method_and_bank_account(): void
    {
        $tenant = Tenant::query()->create([
            'company_name' => 'Reservation Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $company = Company::query()->create(['tenant_id' => $tenant->id, 'name' => 'Co']);
        $driver = Driver::query()->create($this->agreementReadyDriverAttributes($tenant->id, [
            'first_name' => 'Pay',
            'last_name' => 'Test',
            'email' => 'pay@example.com',
            'phone_number' => '07000000009',
            'driver_license_number' => 'LIC000005',
        ]));

        $bankAccountId = DB::table('bank_accounts')->insertGetId([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'bank_name' => 'Barclays',
            'account_number' => '99887766',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $carModelId = DB::table('car_models')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => 'Test Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $counselId = DB::table('counsels')->insertGetId([
            'name' => 'Test Counsel',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $car = Car::query()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'car_model_id' => $carModelId,
            'registration' => 'PAY001',
            'color' => 'Black',
            'vin' => 'VINPAY001',
            'manufacture_year' => 2020,
            'registration_year' => 2020,
            'purchase_date' => '2020-01-01',
            'purchase_price' => 10000,
            'purchase_type' => 'uk',
            'fleet_status' => 'reserved',
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

        $activeStatus = Status::query()->create(['name' => 'Active', 'type' => 'agreement']);

        $user = User::factory()->create();
        $user->tenants()->attach($tenant->id, [
            'role' => 'admin',
            'is_primary' => true,
            'joined_at' => now(),
        ]);
        $this->actingAs($user);
        $user->switchTenant($tenant->id);

        $reservation = CarReservation::query()->create([
            'tenant_id' => $tenant->id,
            'car_id' => $car->id,
            'driver_id' => $driver->id,
            'customer_name' => 'Pay Test',
            'reservation_date' => '2026-06-25',
            'pick_up_date' => '2026-06-30',
            'agreed_rent' => 150,
            'agreed_advance' => 200,
            'amount_paid' => 50,
            'payment_method' => 'Bank Transfer',
            'bank_account_id' => $bankAccountId,
            'balance_payable_on_pickup' => 300,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $response = $this->from(route('agreements.create'))
            ->post(route('agreements.store'), [
                'reservation_id' => $reservation->id,
                'company_id' => $company->id,
                'driver_id' => $driver->id,
                'car_id' => $car->id,
                'start_date' => '2026-06-30T09:00',
                'end_date' => '2027-06-30',
                'agreed_rent' => 150,
                'rent_interval' => 'Weekly',
                'collection_type' => 'weekly',
                'deposit_amount' => 200,
                'status_id' => $activeStatus->id,
                'add_payment' => 1,
                'agreement_payments' => [
                    [
                        'payment_method' => $reservation->payment_method,
                        'bank_account_id' => $reservation->bank_account_id,
                        'payment_date' => '2026-06-30',
                        'amount' => 50,
                        'notes' => 'Payment from reservation #'.$reservation->id,
                    ],
                ],
            ]);

        $response->assertRedirect(route('agreements.index'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payments', [
            'driver_id' => $driver->id,
            'payment_method' => 'Bank Transfer',
            'bank_account_id' => $bankAccountId,
            'amount' => 50,
        ]);
    }

    private function setUpHttpTestExtras(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->default(1);
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->date('dob')->nullable();
            $table->string('address1')->nullable();
            $table->string('post_code')->nullable();
            $table->string('town')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->string('driver_license_number')->nullable();
            $table->date('driver_license_expiry_date')->nullable();
            $table->string('next_of_kin')->nullable();
            $table->string('next_of_kin_phone')->nullable();
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

        Schema::table('agreements', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_agreement_id')->nullable();
            $table->json('mutual_detail_slip_document')->nullable();
        });

        Schema::table('car_reservations', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('driver_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->date('reservation_date')->nullable();
            $table->date('pick_up_date')->nullable();
            $table->decimal('agreed_rent', 12, 2)->nullable();
            $table->decimal('agreed_advance', 12, 2)->nullable();
            $table->decimal('amount_paid', 12, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->decimal('balance_payable_on_pickup', 12, 2)->nullable();
            $table->foreignId('created_by')->nullable();
        });

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

        Schema::create('tenant_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('user_id');
            $table->string('role')->default('admin');
            $table->boolean('is_primary')->default(true);
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
        });

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

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function agreementReadyDriverAttributes(int $tenantId, array $overrides = []): array
    {
        return array_merge([
            'tenant_id' => $tenantId,
            'first_name' => 'Test',
            'last_name' => 'Driver',
            'dob' => '1990-01-01',
            'email' => 'driver@example.com',
            'phone_number' => '07000000001',
            'address1' => '1 Test Street',
            'post_code' => 'SW1A 1AA',
            'town' => 'London',
            'country_id' => 1,
            'driver_license_number' => 'LIC000000',
            'driver_license_expiry_date' => '2027-01-01',
            'next_of_kin' => 'Jane Driver',
            'next_of_kin_phone' => '07000000099',
        ], $overrides);
    }
}
