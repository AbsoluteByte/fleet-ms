<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarPhvlProgress;
use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\Concerns\SetupPhvlManagementDatabase;
use Tests\TestCase;

class PhvlManagementFleetStatusTest extends TestCase
{
    use SetupPhvlManagementDatabase;

    private Tenant $tenant;

    private Company $company;

    private int $carModelId;

    private User $user;

    private Car $eligibleCar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpPhvlManagementDatabase();

        $this->tenant = Tenant::create([
            'company_name' => 'PHVL Test Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->company = Company::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'PHVL Test Company',
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

        $this->eligibleCar = $this->createCar('ELIG001', Car::FLEET_STATUS_PREPARATION_FOR_PHVL);
    }

    protected function tearDown(): void
    {
        $this->tearDownPhvlManagementDatabase();

        parent::tearDown();
    }

    public function test_phvl_data_excludes_terminal_fleet_statuses(): void
    {
        $excludedStatuses = Car::fleetStatusesExcludedFromPhvlManagement();

        foreach ($excludedStatuses as $index => $status) {
            $this->createCar('EXCL'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT), $status);
        }

        $response = $this->getJson(route('phvl.data', ['type' => 'all']));

        $response->assertOk();

        $registrations = collect($response->json('data'))->pluck('registration')->all();

        $this->assertSame(['ELIG001'], $registrations);
    }

    public function test_phvl_progress_update_is_forbidden_for_excluded_fleet_status(): void
    {
        $writtenOffCar = $this->createCar('WRIT001', Car::FLEET_STATUS_WRITTEN_OFF);

        $response = $this->patchJson(route('phvl.update-progress', $writtenOffCar), [
            'mot_status' => 'done',
        ]);

        $response->assertForbidden();
    }

    public function test_phvl_data_filters_by_mot_status(): void
    {
        $motDoneCar = $this->createCar('MOTDONE', Car::FLEET_STATUS_PREPARATION_FOR_PHVL);
        $motPendingCar = $this->createCar('MOTPEND', Car::FLEET_STATUS_PREPARATION_FOR_PHVL);

        $this->createProgress($motDoneCar, ['mot_status' => 'done']);
        $this->createProgress($motPendingCar, ['mot_status' => 'pending']);

        $response = $this->getJson(route('phvl.data', [
            'type' => 'all',
            'mot_status' => 'done',
        ]));

        $response->assertOk();

        $registrations = collect($response->json('data'))->pluck('registration')->all();

        $this->assertContains('MOTDONE', $registrations);
        $this->assertNotContains('MOTPEND', $registrations);
        $this->assertNotContains('ELIG001', $registrations);
    }

    public function test_phvl_data_filters_by_application_status(): void
    {
        $appliedCar = $this->createCar('APPAPPL', Car::FLEET_STATUS_PREPARATION_FOR_PHVL);
        $pendingCar = $this->createCar('APPPEND', Car::FLEET_STATUS_PREPARATION_FOR_PHVL);

        $this->createProgress($appliedCar, ['application_status' => 'applied']);
        $this->createProgress($pendingCar, ['application_status' => 'pending']);

        $response = $this->getJson(route('phvl.data', [
            'type' => 'all',
            'application_status' => 'applied',
        ]));

        $response->assertOk();

        $registrations = collect($response->json('data'))->pluck('registration')->all();

        $this->assertContains('APPAPPL', $registrations);
        $this->assertNotContains('APPPEND', $registrations);
        $this->assertNotContains('ELIG001', $registrations);
    }

    public function test_phvl_data_treats_missing_progress_as_pending_for_status_filters(): void
    {
        $response = $this->getJson(route('phvl.data', [
            'type' => 'all',
            'mot_status' => 'pending',
            'application_status' => 'pending',
        ]));

        $response->assertOk();

        $registrations = collect($response->json('data'))->pluck('registration')->all();

        $this->assertContains('ELIG001', $registrations);
    }

    public function test_phvl_data_filters_by_appointment_confirmation(): void
    {
        $approvedCar = $this->createCar('APPTAPPR', Car::FLEET_STATUS_PREPARATION_FOR_PHVL);
        $pendingCar = $this->createCar('APPTPEND', Car::FLEET_STATUS_PREPARATION_FOR_PHVL);

        $this->createProgress($approvedCar, ['appointment_confirmation' => 'approved']);
        $this->createProgress($pendingCar, ['appointment_confirmation' => 'pending']);

        $response = $this->getJson(route('phvl.data', [
            'type' => 'all',
            'appointment_confirmation' => 'approved',
        ]));

        $response->assertOk();

        $registrations = collect($response->json('data'))->pluck('registration')->all();

        $this->assertContains('APPTAPPR', $registrations);
        $this->assertNotContains('APPTPEND', $registrations);
        $this->assertNotContains('ELIG001', $registrations);
    }

    public function test_phvl_data_treats_legacy_confirmed_as_approved_for_appointment_confirmation_filter(): void
    {
        $confirmedCar = $this->createCar('APPTCONF', Car::FLEET_STATUS_PREPARATION_FOR_PHVL);

        $this->createProgress($confirmedCar, ['appointment_confirmation' => 'confirmed']);

        $response = $this->getJson(route('phvl.data', [
            'type' => 'all',
            'appointment_confirmation' => 'approved',
        ]));

        $response->assertOk();

        $registrations = collect($response->json('data'))->pluck('registration')->all();

        $this->assertContains('APPTCONF', $registrations);
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createProgress(Car $car, array $overrides = []): CarPhvlProgress
    {
        return CarPhvlProgress::query()->create(array_merge([
            'tenant_id' => $this->tenant->id,
            'car_id' => $car->id,
            'mot_status' => 'pending',
            'application_status' => 'pending',
            'appointment_confirmation' => 'pending',
        ], $overrides));
    }
}
