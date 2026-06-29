<?php

namespace Tests\Feature;

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
        Schema::dropIfExists('agreement_collections');
        Schema::dropIfExists('car_status_histories');
        Schema::dropIfExists('tenant_user');
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

        $response = $this->post(route('agreements.store'), [
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
        $driver = Driver::query()->create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Sara',
            'last_name' => 'Ali',
            'email' => 'sara@example.com',
            'phone_number' => '07000000004',
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

        $response = $this->post(route('agreements.store'), [
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
        $driver = Driver::query()->create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Omar',
            'last_name' => 'Hassan',
            'email' => 'omar@example.com',
            'phone_number' => '07000000005',
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

        $response = $this->post(route('agreements.store'), [
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
        $driver = Driver::query()->create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Noor',
            'last_name' => 'Ali',
            'email' => 'noor@example.com',
            'phone_number' => '07000000006',
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

        $response = $this->post(route('agreements.store'), [
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

    private function setUpHttpTestExtras(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->default(1);
        });

        Schema::table('agreements', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_agreement_id')->nullable();
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
            $table->decimal('balance_payable_on_pickup', 12, 2)->nullable();
            $table->foreignId('created_by')->nullable();
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
}
