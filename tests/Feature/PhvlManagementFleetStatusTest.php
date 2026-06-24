<?php

namespace Tests\Feature;

use App\Models\Car;
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
}
