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

    private DriverAgreementStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAgreementChangeCarDatabase();
        Schema::table('drivers', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });
        Schema::table('agreements', function (Blueprint $table) {
            $table->string('paying_company_name')->nullable();
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
        $this->actingAs($user);

        $this->service = app(DriverAgreementStatusService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
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
            'collection_type' => 'manual',
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
}
