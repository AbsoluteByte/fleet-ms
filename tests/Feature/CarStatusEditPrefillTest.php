<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarStatusHistory;
use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PhvlSuspensionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\Concerns\SetupPhvlManagementDatabase;
use Tests\TestCase;

class CarStatusEditPrefillTest extends TestCase
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
            'company_name' => 'Car Status Edit Test Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->company = Company::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Car Status Edit Test Company',
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

    public function test_edit_damaged_status_prefills_date_and_claim_fields(): void
    {
        $car = $this->createCar('EDIT001', 'damaged');
        $prefillStatusPayload = [
            'damage_date' => '2026-06-01',
            'incident_date' => '2026-05-30',
            'fault_type' => 'non_fault',
            'insurance_status' => 'company',
            'insurance_excess_amount' => 500,
            'insurance_claim_reference' => 'CLM-EDIT-1',
            'claim_handler_name' => 'Jane Handler',
            'phvl_suspension_status' => PhvlSuspensionService::STATUS_SUSPENDED,
            'phvl_suspension_status_date' => '2026-06-02',
            'phvl_suspension_notes' => 'Awaiting council',
        ];

        View::share('errors', new ViewErrorBag);

        $html = view('backend.car_status.partials.wizard_step2', [
            'cars' => collect([$car]),
            'drivers' => collect(),
            'fleetLabels' => ['damaged' => 'Damaged'],
            'carFleetFlags' => [],
            'prefillCarId' => $car->id,
            'prefillTargetStatus' => 'damaged',
            'prefillStatusPayload' => $prefillStatusPayload,
            'editCurrentStatus' => true,
            'bankAccounts' => collect(),
        ])->render();

        $this->assertStringContainsString('value="2026-06-01"', $html);
        $this->assertStringContainsString('value="2026-05-30"', $html);
        $this->assertStringContainsString('value="2026-06-02"', $html);
        $this->assertStringContainsString('value="CLM-EDIT-1"', $html);
        $this->assertStringContainsString('value="Jane Handler"', $html);
        $this->assertStringContainsString('Awaiting council', $html);
        $this->assertStringContainsString('name="payload[claim_handler_name]"', $html);
    }

    public function test_fault_damaged_edit_persists_phvl_and_claim_handler_without_claim_reference(): void
    {
        $car = $this->createCar('EDIT002', 'damaged');
        $history = CarStatusHistory::create([
            'tenant_id' => $this->tenant->id,
            'car_id' => $car->id,
            'previous_status' => Car::FLEET_STATUS_AVAILABLE_FOR_RENT,
            'new_status' => 'damaged',
            'status_data' => [
                'damage_date' => '2026-06-01',
                'incident_date' => '2026-05-30',
                'fault_type' => 'fault',
                'insurance_status' => 'driver',
                'insurance_excess_amount' => 250,
                'excess_status' => 'Pending',
                'fault_notes' => 'Driver at fault',
                'phvl_suspension_status' => PhvlSuspensionService::STATUS_ACTIVE,
                'claim_handler_name' => 'John Handler',
            ],
            'changed_by' => $this->user->id,
        ]);

        $response = $this->put(route('car-status.current.update', $car), [
            'edit_current_status' => 1,
            'target_status' => 'damaged',
            'payload' => [
                'damage_date' => '2026-06-01',
                'incident_date' => '2026-05-30',
                'fault_type' => 'fault',
                'insurance_status' => 'driver',
                'insurance_excess_amount' => 250,
                'excess_status' => 'Pending',
                'fault_notes' => 'Driver at fault',
                'insurance_claim_reference' => 'CLM-LATER',
                'claim_handler_name' => 'John Handler',
                'phvl_suspension_status' => PhvlSuspensionService::STATUS_SUSPENDED,
                'phvl_suspension_status_date' => '2026-06-03',
                'phvl_suspension_notes' => 'Suspended after review',
            ],
        ]);

        $response->assertRedirect(route('cars.show', $car));

        $history->refresh();
        $this->assertSame('CLM-LATER', $history->status_data['insurance_claim_reference']);
        $this->assertSame('John Handler', $history->status_data['claim_handler_name']);
        $this->assertSame(PhvlSuspensionService::STATUS_SUSPENDED, $history->status_data['phvl_suspension_status']);
        $this->assertSame('2026-06-03', $history->status_data['phvl_suspension_status_date']);
        $this->assertSame('Suspended after review', $history->status_data['phvl_suspension_notes']);
    }

    public function test_written_off_edit_saves_optional_claim_fields(): void
    {
        $car = $this->createCar('EDIT003', 'written_off');
        $history = CarStatusHistory::create([
            'tenant_id' => $this->tenant->id,
            'car_id' => $car->id,
            'previous_status' => 'damaged',
            'new_status' => 'written_off',
            'status_data' => [
                'disposal_outcome' => 'disposed_by_insurer',
                'incident_date' => '2026-05-20',
                'fault_type' => 'non_fault',
                'insurance_status' => 'company',
                'insurance_excess_amount' => 1000,
            ],
            'changed_by' => $this->user->id,
        ]);

        $response = $this->put(route('car-status.current.update', $car), [
            'edit_current_status' => 1,
            'target_status' => 'written_off',
            'payload' => [
                'disposal_outcome' => 'disposed_by_insurer',
                'incident_date' => '2026-05-20',
                'fault_type' => 'non_fault',
                'insurance_status' => 'company',
                'insurance_excess_amount' => 1000,
                'claim_handler_name' => 'Written Off Handler',
            ],
        ]);

        $response->assertRedirect(route('cars.show', $car));

        $history->refresh();
        $this->assertNull($history->status_data['insurance_claim_reference'] ?? null);
        $this->assertSame('Written Off Handler', $history->status_data['claim_handler_name']);
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
