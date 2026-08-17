<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Car;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Status;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AgreementUpgradeService;
use App\Services\DriverAgreementStatusService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\Concerns\SetupAgreementChangeCarDatabase;
use Tests\TestCase;

class DriverAgreementStatusSyncTest extends TestCase
{
    use SetupAgreementChangeCarDatabase;

    private Tenant $tenant;

    private Company $company;

    private Driver $driver;

    private Car $car;

    private Car $secondCar;

    private Status $activeStatus;

    private Status $swapStatus;

    private Status $terminatedStatus;

    private User $user;

    private DriverAgreementStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpAgreementChangeCarDatabase();
        $this->setUpHttpTestExtras();
        Schema::table('agreements', function (Blueprint $table) {
            $table->string('paying_company_name')->nullable();
            $table->json('mutual_detail_slip_document')->nullable();
            $table->unsignedBigInteger('parent_agreement_id')->nullable();
        });

        Carbon::setTestNow(Carbon::parse('2026-06-18 10:00:00'));

        $this->tenant = Tenant::create(['company_name' => 'Status Sync Tenant']);
        $this->company = Company::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Status Sync Company',
        ]);

        $this->driver = Driver::create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Sync',
            'last_name' => 'Driver',
            'email' => 'sync@example.com',
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
            'is_active' => false,
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
        $this->swapStatus = Status::create(['name' => 'Swap', 'type' => 'agreement']);
        $this->terminatedStatus = Status::create(['name' => 'Terminated', 'type' => 'agreement']);

        $user = User::factory()->create();
        $this->user = $user;
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

        $this->service = app(DriverAgreementStatusService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Schema::dropIfExists('car_status_histories');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('deposit_refunds');
        Schema::dropIfExists('agreement_deductions');
        Schema::dropIfExists('agreement_additional_charges');
        Schema::dropIfExists('agreement_collections');
        Schema::dropIfExists('countries');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('tenant_user');
        $this->tearDownAgreementChangeCarDatabase();

        parent::tearDown();
    }

    public function test_active_agreement_sync_activates_driver(): void
    {
        $agreement = $this->createAgreement($this->car, $this->activeStatus);

        $this->service->syncForAgreement($agreement);

        $this->assertTrue($this->driver->fresh()->is_active);
    }

    public function test_terminating_only_agreement_deactivates_driver(): void
    {
        $agreement = $this->createAgreement($this->car, $this->activeStatus);
        $this->service->syncForAgreement($agreement);
        $this->assertTrue($this->driver->fresh()->is_active);

        $agreement->update(['status_id' => $this->terminatedStatus->id]);

        $this->service->syncForAgreement($agreement->fresh());

        $this->assertFalse($this->driver->fresh()->is_active);
    }

    public function test_swap_keeps_driver_active(): void
    {
        $agreement = $this->createAgreement($this->car, $this->activeStatus);
        $this->assertFalse($this->driver->fresh()->is_active);

        app(AgreementUpgradeService::class)->createSwapFromAgreement($agreement, [
            'car_id' => $this->secondCar->id,
            'driver_id' => $this->driver->id,
            'agreed_rent' => 250,
        ]);

        $this->assertTrue($this->driver->fresh()->is_active);
    }

    public function test_driver_stays_active_when_one_of_two_active_agreements_is_terminated(): void
    {
        $firstAgreement = $this->createAgreement($this->car, $this->activeStatus);
        $secondAgreement = $this->createAgreement($this->secondCar, $this->activeStatus);
        $this->service->syncForAgreement($secondAgreement);
        $this->assertTrue($this->driver->fresh()->is_active);

        $firstAgreement->update(['status_id' => $this->terminatedStatus->id]);
        $this->service->syncForAgreement($firstAgreement->fresh());

        $this->assertTrue($this->driver->fresh()->is_active);
    }

    public function test_cron_deactivates_driver_without_billable_agreements(): void
    {
        $this->driver->update(['is_active' => true]);

        Artisan::call('drivers:sync-agreement-status');

        $this->assertFalse($this->driver->fresh()->is_active);
        $this->assertStringContainsString('Updated 1 driver(s).', Artisan::output());
    }

    public function test_cron_deactivates_driver_when_only_agreement_is_expired(): void
    {
        $agreement = $this->createAgreement($this->car, $this->activeStatus, [
            'end_date' => Carbon::parse('2026-06-17'),
        ]);
        $this->driver->update(['is_active' => true]);

        Artisan::call('drivers:sync-agreement-status');

        $this->assertFalse($this->driver->fresh()->is_active);
        $this->service->syncForAgreement($agreement->fresh());
        $this->assertFalse($this->driver->fresh()->is_active);
    }

    public function test_expired_holdover_keeps_driver_active(): void
    {
        $expiredStatus = Status::create(['name' => 'Expired', 'type' => 'agreement']);
        $this->createAgreement($this->car, $expiredStatus, [
            'end_date' => Carbon::parse('2026-06-17'),
        ]);
        $this->driver->update(['is_active' => false]);

        Artisan::call('drivers:sync-agreement-status');

        $this->assertTrue($this->driver->fresh()->is_active);
    }

    public function test_agreement_create_form_shows_inactive_drivers(): void
    {
        Driver::create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Active',
            'last_name' => 'Only',
            'email' => 'active-only@example.com',
            'phone_number' => '07000000003',
            'is_active' => true,
        ]);

        $response = $this->get(route('agreements.create'));

        $response->assertOk();
        $response->assertSee('name="driver_id"', false);
        $response->assertSee('value="'.$this->driver->id.'"', false);
        $response->assertSee('Sync  Driver (SW1A 1AA) (Inactive)', false);
    }

    public function test_agreement_edit_form_shows_other_inactive_drivers(): void
    {
        $inactiveOther = Driver::create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Other',
            'last_name' => 'Inactive',
            'email' => 'other-inactive@example.com',
            'phone_number' => '07000000004',
            'post_code' => 'E1 1AA',
            'is_active' => false,
        ]);

        $agreement = $this->createAgreement($this->car, $this->activeStatus);

        $response = $this->get(route('agreements.edit', $agreement));

        $response->assertOk();
        $response->assertSee('value="'.$inactiveOther->id.'"', false);
        $response->assertSee('Other  Inactive (E1 1AA) (Inactive)', false);
    }

    public function test_creating_active_agreement_via_store_activates_inactive_driver(): void
    {
        $this->assertFalse($this->driver->fresh()->is_active);

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
        $this->assertTrue($this->driver->fresh()->is_active);
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
            'fleet_status' => 'available_for_rent',
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
        });
        if (! Schema::hasColumn('drivers', 'is_active')) {
            Schema::table('drivers', function (Blueprint $table) {
                $table->boolean('is_active')->default(true);
            });
        }

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

        Schema::create('agreement_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('agreement_id');
            $table->decimal('amount', 12, 2);
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('agreement_additional_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('agreement_id');
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('deposit_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('agreement_id')->unique();
            $table->foreignId('driver_id');
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('gross_deposit_amount', 12, 2)->default(0);
            $table->decimal('deductions_amount', 12, 2)->default(0);
            $table->decimal('debt_offset_amount', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
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
    }
}
