<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarStatusHistory;
use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PhvlSuspensionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\Concerns\SetupPhvlManagementDatabase;
use Tests\TestCase;

class PhvlDamagedCarsTest extends TestCase
{
    use SetupPhvlManagementDatabase;

    private Tenant $tenant;

    private Company $company;

    private int $carModelId;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpPhvlManagementDatabase();

        $this->tenant = Tenant::create([
            'company_name' => 'PHVL Damaged Test Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->company = Company::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'PHVL Damaged Test Company',
        ]);

        $this->carModelId = (int) DB::table('car_models')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Model',
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
        $this->tearDownPhvlManagementDatabase();

        parent::tearDown();
    }

    public function test_non_fault_damaged_with_suspended_saves_snapshot_history_and_appears_in_suspended_tab(): void
    {
        $car = $this->createCar('DMG001', Car::FLEET_STATUS_AVAILABLE_FOR_RENT);

        $response = $this->post(route('car-status.store'), [
            'car_id' => $car->id,
            'target_status' => 'damaged',
            'payload' => [
                'damage_date' => '2026-06-01',
                'insurance_status' => 'company',
                'insurance_excess_amount' => 500,
                'fault_type' => 'non_fault',
                'incident_date' => '2026-05-30',
                'insurance_claim_reference' => 'CLM-100',
                'phvl_suspension_status' => PhvlSuspensionService::STATUS_SUSPENDED,
                'phvl_suspension_status_date' => '2026-06-01',
            ],
        ]);

        $response->assertRedirect(route('cars.show', $car));

        $car->refresh();
        $this->assertSame('damaged', $car->fleet_status);
        $this->assertSame(PhvlSuspensionService::STATUS_SUSPENDED, $car->phvl_suspension_status);
        $this->assertSame('2026-06-01', $car->phvl_suspension_status_date->toDateString());

        $this->assertDatabaseHas('car_phvl_suspension_histories', [
            'car_id' => $car->id,
            'to_status' => PhvlSuspensionService::STATUS_SUSPENDED,
        ]);

        $suspendedResponse = $this->getJson(route('phvl.damaged-cars.data', ['tab' => 'suspended']));
        $suspendedResponse->assertOk();
        $registrations = collect($suspendedResponse->json('data'))->pluck('registration')->all();
        $this->assertContains('DMG001', $registrations);

        $activeResponse = $this->getJson(route('phvl.damaged-cars.data', ['tab' => 'active']));
        $activeResponse->assertOk();
        $this->assertNotContains('DMG001', collect($activeResponse->json('data'))->pluck('registration')->all());
    }

    public function test_fault_damaged_saves_without_claim_reference_and_applies_phvl_status(): void
    {
        $car = $this->createCar('DMG002', Car::FLEET_STATUS_AVAILABLE_FOR_RENT);

        $response = $this->post(route('car-status.store'), [
            'car_id' => $car->id,
            'target_status' => 'damaged',
            'payload' => [
                'damage_date' => '2026-06-01',
                'insurance_status' => 'driver',
                'insurance_excess_amount' => 250,
                'fault_type' => 'fault',
                'incident_date' => '2026-05-30',
                'excess_status' => 'Pending',
                'fault_notes' => 'Driver at fault',
                'phvl_suspension_status' => PhvlSuspensionService::STATUS_ACTIVE,
            ],
        ]);

        $response->assertRedirect(route('cars.show', $car));

        $car->refresh();
        $this->assertNull($car->phvl_suspension_status);

        $history = CarStatusHistory::query()->where('car_id', $car->id)->latest('id')->firstOrFail();
        $this->assertSame('fault', $history->status_data['fault_type']);
        $this->assertNull($history->status_data['insurance_claim_reference'] ?? null);
        $this->assertSame(PhvlSuspensionService::STATUS_ACTIVE, $history->status_data['phvl_suspension_status']);

        $allResponse = $this->getJson(route('phvl.damaged-cars.data', ['tab' => 'all']));
        $allResponse->assertOk();
        $this->assertNotContains('DMG002', collect($allResponse->json('data'))->pluck('registration')->all());
    }

    public function test_uplift_transition_moves_tab_and_records_date(): void
    {
        $car = $this->createCar('DMG003', 'damaged');
        $this->seedNonFaultDamagedHistory($car);
        $car->update([
            'phvl_suspension_status' => PhvlSuspensionService::STATUS_SUSPENDED,
            'phvl_suspension_status_date' => '2026-04-01',
        ]);

        $response = $this->patchJson(route('phvl.damaged-cars.update-status', $car), [
            'phvl_suspension_status' => PhvlSuspensionService::STATUS_SUSPENSION_UPLIFTED,
            'phvl_suspension_status_date' => '2026-06-15',
            'phvl_suspension_notes' => 'Council uplifted suspension',
        ]);

        $response->assertOk();

        $car->refresh();
        $this->assertSame(PhvlSuspensionService::STATUS_SUSPENSION_UPLIFTED, $car->phvl_suspension_status);
        $this->assertSame('2026-06-15', $car->phvl_suspension_status_date->toDateString());
        $this->assertSame('damaged', $car->fleet_status);

        $this->assertDatabaseHas('car_phvl_suspension_histories', [
            'car_id' => $car->id,
            'from_status' => PhvlSuspensionService::STATUS_SUSPENDED,
            'to_status' => PhvlSuspensionService::STATUS_SUSPENSION_UPLIFTED,
            'notes' => 'Council uplifted suspension',
        ]);

        $suspendedResponse = $this->getJson(route('phvl.damaged-cars.data', ['tab' => 'suspended']));
        $this->assertNotContains('DMG003', collect($suspendedResponse->json('data'))->pluck('registration')->all());

        $upliftedResponse = $this->getJson(route('phvl.damaged-cars.data', ['tab' => 'suspension_uplifted']));
        $this->assertContains('DMG003', collect($upliftedResponse->json('data'))->pluck('registration')->all());
    }

    public function test_licence_revoked_blocks_agreement_selection_and_sets_phv_status(): void
    {
        $car = $this->createCar('DMG004', Car::FLEET_STATUS_AVAILABLE_FOR_RENT);
        $car->update(['phv_status' => 'phv_active']);

        $service = app(PhvlSuspensionService::class);
        $service->applyStatus(
            $car,
            PhvlSuspensionService::STATUS_LICENCE_REVOKED,
            Carbon::parse('2026-06-10'),
            'Council revoked licence'
        );

        $car->refresh();
        $this->assertTrue($car->hasPhvlLicenceRevoked());
        $this->assertFalse($car->isEligibleForAgreementSelection());
        $this->assertFalse($car->isSelectableForAgreement([]));
        $this->assertSame('need_to_apply', $car->phv_status);
    }

    public function test_days_suspended_and_sixty_day_warning_calculation(): void
    {
        $car = $this->createCar('DMG005', 'damaged');
        $car->update([
            'phvl_suspension_status' => PhvlSuspensionService::STATUS_SUSPENDED,
            'phvl_suspension_status_date' => now()->subDays(50)->toDateString(),
        ]);

        $service = app(PhvlSuspensionService::class);

        $this->assertSame(50, $service->daysSuspended($car));
        $this->assertSame(10, $service->daysUntilSuspensionLimit($car));
        $this->assertSame('warning', $service->suspensionWarningLevel($car));

        $car->update(['phvl_suspension_status_date' => now()->subDays(62)->toDateString()]);
        $car->refresh();

        $this->assertSame(62, $service->daysSuspended($car));
        $this->assertSame('danger', $service->suspensionWarningLevel($car));
        $this->assertStringContainsString('60-day limit reached', (string) $service->suspensionWarningLabel($car));
    }

    public function test_damaged_cars_data_includes_warning_badge_for_suspended_car(): void
    {
        $car = $this->createCar('DMG006', 'damaged');
        $this->seedNonFaultDamagedHistory($car);
        $car->update([
            'phvl_suspension_status' => PhvlSuspensionService::STATUS_SUSPENDED,
            'phvl_suspension_status_date' => now()->subDays(30)->toDateString(),
        ]);

        $response = $this->getJson(route('phvl.damaged-cars.data', ['tab' => 'suspended']));
        $response->assertOk();

        $row = collect($response->json('data'))->firstWhere('registration', 'DMG006');
        $this->assertNotNull($row);
        $this->assertSame('30', $row['days_suspended']);
        $this->assertStringContainsString('badge-success', $row['suspension_warning']);
    }

    private function createCar(string $registration, string $fleetStatus): Car
    {
        return Car::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'car_model_id' => $this->carModelId,
            'registration' => $registration,
            'fleet_status' => $fleetStatus,
            'createdBy' => $this->user->id,
            'updatedBy' => $this->user->id,
        ]);
    }

    private function seedNonFaultDamagedHistory(Car $car): CarStatusHistory
    {
        return CarStatusHistory::create([
            'tenant_id' => $this->tenant->id,
            'car_id' => $car->id,
            'previous_status' => Car::FLEET_STATUS_AVAILABLE_FOR_RENT,
            'new_status' => 'damaged',
            'status_data' => [
                'damage_date' => '2026-05-01',
                'incident_date' => '2026-04-28',
                'fault_type' => 'non_fault',
                'insurance_claim_reference' => 'CLM-SEED',
                'insurance_status' => 'company',
                'insurance_excess_amount' => 500,
            ],
            'changed_by' => $this->user->id,
        ]);
    }
}
