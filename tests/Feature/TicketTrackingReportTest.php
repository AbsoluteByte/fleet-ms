<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Car;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Status;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TicketTrackingReportService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\Concerns\SetupAgreementChangeCarDatabase;
use Tests\TestCase;

class TicketTrackingReportTest extends TestCase
{
    use SetupAgreementChangeCarDatabase;

    private Tenant $tenant;

    private Company $company;

    private Driver $driver;

    private Car $car;

    private Car $otherCar;

    private Status $activeStatus;

    private Status $terminatedStatus;

    private Status $pendingStatus;

    private Status $replacementStatus;

    private User $user;

    private TicketTrackingReportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpAgreementChangeCarDatabase();
        $this->setUpHttpTestExtras();

        Carbon::setTestNow(Carbon::parse('2026-06-18 10:00:00'));

        $this->service = app(TicketTrackingReportService::class);

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Ticket Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $this->company = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Ticket Company',
        ]);
        $this->driver = Driver::query()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Ticket',
            'last_name' => 'Driver',
            'email' => 'ticket@example.com',
            'phone_number' => '07000000099',
            'driver_license_number' => 'TICKET123',
        ]);

        $carModelId = DB::table('car_models')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'name' => 'Ticket Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $counselId = DB::table('counsels')->insertGetId([
            'name' => 'Ticket Counsel',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->car = $this->createCompliantCar('TCK001', $carModelId, $counselId, 'rented');
        $this->otherCar = $this->createCompliantCar('TCK002', $carModelId, $counselId, 'available_for_rent');

        $this->activeStatus = Status::query()->create(['name' => 'Active', 'type' => 'agreement']);
        $this->terminatedStatus = Status::query()->create(['name' => 'Terminated', 'type' => 'agreement']);
        $this->pendingStatus = Status::query()->create(['name' => 'Pending', 'type' => 'agreement']);
        $this->replacementStatus = Status::query()->create(['name' => 'Replacement Vehicle', 'type' => 'agreement']);

        $this->user = User::factory()->create();
        $this->assignAdminRole($this->user);
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
        Carbon::setTestNow();
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('insurance_providers');
        Schema::dropIfExists('tenant_user');
        $this->tearDownAgreementChangeCarDatabase();

        parent::tearDown();
    }

    public function test_finds_active_agreement_driver_at_midday(): void
    {
        $agreement = $this->createAgreement($this->car, $this->activeStatus, '2026-06-01 09:00:00', '2027-06-01');

        $result = $this->service->findAssignment(
            $this->tenant->id,
            $this->car->id,
            Carbon::parse('2026-06-10 12:00:00')
        );

        $this->assertNotNull($result);
        $this->assertSame($agreement->id, $result->id);
        $this->assertSame($this->driver->id, $result->driver_id);
    }

    public function test_returns_null_before_agreement_start(): void
    {
        $this->createAgreement($this->car, $this->activeStatus, '2026-06-01 09:00:00', '2027-06-01');

        $result = $this->service->findAssignment(
            $this->tenant->id,
            $this->car->id,
            Carbon::parse('2026-05-31 23:59:00')
        );

        $this->assertNull($result);
    }

    public function test_returns_null_after_contract_end_time_on_end_date(): void
    {
        $this->createAgreement($this->car, $this->activeStatus, '2026-06-01 09:00:00', '2026-06-20');

        $beforeEnd = $this->service->findAssignment(
            $this->tenant->id,
            $this->car->id,
            Carbon::parse('2026-06-20 10:30:00')
        );
        $atEnd = $this->service->findAssignment(
            $this->tenant->id,
            $this->car->id,
            Carbon::parse('2026-06-20 11:00:00')
        );

        $this->assertNotNull($beforeEnd);
        $this->assertNull($atEnd);
    }

    public function test_vehicle_swap_old_car_before_swap_and_new_car_after_swap(): void
    {
        $oldAgreement = $this->createAgreement($this->car, $this->activeStatus, '2026-06-01 09:00:00', '2027-06-01');

        $swapAt = Carbon::parse('2026-06-18 14:00:00');

        $oldAgreement->update([
            'status_id' => $this->terminatedStatus->id,
            'end_date' => $swapAt->toDateString(),
            'termination_notice_date' => $swapAt->toDateString(),
        ]);

        $newAgreement = Agreement::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $this->otherCar->id,
            'start_date' => $swapAt,
            'end_date' => '2027-06-01',
            'agreed_rent' => 200,
            'rent_interval' => 'weekly',
            'deposit_amount' => 500,
            'collection_type' => 'weekly',
            'using_own_insurance' => false,
            'status_id' => $this->activeStatus->id,
            'upgraded_from_agreement_id' => $oldAgreement->id,
            'createdBy' => $this->user->id,
            'updatedBy' => $this->user->id,
        ]);

        $beforeSwapOldCar = $this->service->findAssignment(
            $this->tenant->id,
            $this->car->id,
            Carbon::parse('2026-06-18 10:00:00')
        );
        $afterSwapOldCar = $this->service->findAssignment(
            $this->tenant->id,
            $this->car->id,
            Carbon::parse('2026-06-18 15:00:00')
        );
        $afterSwapNewCar = $this->service->findAssignment(
            $this->tenant->id,
            $this->otherCar->id,
            Carbon::parse('2026-06-18 15:00:00')
        );

        $this->assertSame($oldAgreement->id, $beforeSwapOldCar?->id);
        $this->assertNull($afterSwapOldCar);
        $this->assertSame($newAgreement->id, $afterSwapNewCar?->id);
        $this->assertSame($this->driver->id, $afterSwapNewCar?->driver_id);
    }

    public function test_closing_date_ends_assignment_before_contract_end(): void
    {
        $this->createAgreement($this->car, $this->terminatedStatus, '2026-06-01 09:00:00', '2027-06-01', [
            'closing_date' => '2026-06-15 16:00:00',
        ]);

        $beforeClosing = $this->service->findAssignment(
            $this->tenant->id,
            $this->car->id,
            Carbon::parse('2026-06-15 15:00:00')
        );
        $afterClosing = $this->service->findAssignment(
            $this->tenant->id,
            $this->car->id,
            Carbon::parse('2026-06-15 17:00:00')
        );

        $this->assertNotNull($beforeClosing);
        $this->assertNull($afterClosing);
    }

    public function test_replacement_vehicle_agreement_on_replacement_car(): void
    {
        $parentAgreement = $this->createAgreement($this->car, $this->activeStatus, '2026-06-01 09:00:00', '2027-06-01');

        $replacementAgreement = Agreement::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $this->otherCar->id,
            'parent_agreement_id' => $parentAgreement->id,
            'start_date' => '2026-06-10 08:00:00',
            'end_date' => '2027-06-01',
            'agreed_rent' => 0,
            'rent_interval' => 'Monthly',
            'deposit_amount' => 0,
            'collection_type' => 'static',
            'using_own_insurance' => false,
            'status_id' => $this->replacementStatus->id,
            'createdBy' => $this->user->id,
            'updatedBy' => $this->user->id,
        ]);

        $result = $this->service->findAssignment(
            $this->tenant->id,
            $this->otherCar->id,
            Carbon::parse('2026-06-12 12:00:00')
        );

        $this->assertNotNull($result);
        $this->assertSame($replacementAgreement->id, $result->id);
        $this->assertSame($this->driver->id, $result->driver_id);
    }

    public function test_pending_agreements_are_excluded(): void
    {
        $this->createAgreement($this->car, $this->pendingStatus, '2026-06-01 09:00:00', '2027-06-01');

        $result = $this->service->findAssignment(
            $this->tenant->id,
            $this->car->id,
            Carbon::parse('2026-06-10 12:00:00')
        );

        $this->assertNull($result);
    }

    public function test_reports_page_renders_ticket_tracking_result(): void
    {
        $agreement = $this->createAgreement($this->car, $this->activeStatus, '2026-06-01 09:00:00', '2027-06-01');

        $response = $this->get(route('reports.index', [
            'ticket_car_id' => $this->car->id,
            'ticket_at' => '2026-06-10T12:00',
        ]));

        $response->assertOk();
        $response->assertSee('Ticket Tracking', false);
        $response->assertSee('Driver found', false);
        $response->assertSee($this->driver->full_name, false);
        $response->assertSee('Agreement #'.$agreement->id, false);
        $response->assertSee('TICKET123', false);
    }

    private function createAgreement(
        Car $car,
        Status $status,
        string $startDate,
        string $endDate,
        array $extra = []
    ): Agreement {
        return Agreement::query()->create(array_merge([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $car->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'agreed_rent' => 200,
            'rent_interval' => 'weekly',
            'deposit_amount' => 500,
            'collection_type' => 'weekly',
            'using_own_insurance' => false,
            'status_id' => $status->id,
            'createdBy' => $this->user->id,
            'updatedBy' => $this->user->id,
        ], $extra));
    }

    private function setUpHttpTestExtras(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->default(1);
        });

        Schema::table('agreements', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_agreement_id')->nullable();
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->string('driver_license_number')->nullable();
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

        Schema::create('insurance_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('provider_name');
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });
    }

    private function assignAdminRole(User $user): void
    {
        $roleId = DB::table('roles')->insertGetId([
            'name' => 'admin',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('model_has_roles')->insert([
            'role_id' => $roleId,
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);
    }

    private function createCompliantCar(string $registration, int $carModelId, int $counselId, string $fleetStatus): Car
    {
        $car = Car::query()->create([
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
            'fleet_status' => $fleetStatus,
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

        return $car->fresh(['mots', 'phvs', 'carModel']);
    }
}
