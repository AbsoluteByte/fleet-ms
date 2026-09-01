<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Car;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\Status;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AgreementInvoiceService;
use App\Services\AgreementUpgradeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SetupAgreementChangeCarDatabase;
use Tests\TestCase;

class RemoveDuplicateSwapInvoicesCommandTest extends TestCase
{
    use SetupAgreementChangeCarDatabase;

    private Tenant $tenant;

    private Company $company;

    private Driver $driver;

    private Car $oldCar;

    private Car $newCar;

    private Status $activeStatus;

    private Status $swapStatus;

    private Status $terminatedStatus;

    private Agreement $agreement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAgreementChangeCarDatabase();

        Carbon::setTestNow(Carbon::parse('2026-06-24 10:00:00'));

        $this->tenant = Tenant::create(['company_name' => 'Duplicate Swap Test Tenant']);
        $this->company = Company::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Duplicate Swap Test Company',
        ]);

        $this->driver = Driver::create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Test',
            'last_name' => 'Driver',
            'email' => 'duplicate-swap@example.com',
            'phone_number' => '07000000001',
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

        $this->oldCar = $this->createCompliantCar('OLD123', $carModelId, $counselId, 'rented');
        $this->newCar = $this->createCompliantCar('NEW456', $carModelId, $counselId, 'available_for_rent');

        $this->activeStatus = Status::create(['name' => 'Active', 'type' => 'agreement']);
        $this->swapStatus = Status::create(['name' => 'Swap', 'type' => 'agreement']);
        $this->terminatedStatus = Status::create(['name' => 'Terminated', 'type' => 'agreement']);

        $user = User::factory()->create();
        $this->actingAs($user);

        $this->agreement = Agreement::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $this->oldCar->id,
            'start_date' => Carbon::parse('2026-06-17'),
            'end_date' => Carbon::parse('2027-06-17'),
            'agreed_rent' => 200,
            'rent_interval' => 'weekly',
            'deposit_amount' => 500,
            'collection_type' => 'manual',
            'using_own_insurance' => false,
            'status_id' => $this->activeStatus->id,
            'createdBy' => $user->id,
            'updatedBy' => $user->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        $this->tearDownAgreementChangeCarDatabase();

        parent::tearDown();
    }

    public function test_dry_run_lists_duplicate_swap_invoice_without_deleting(): void
    {
        $duplicate = $this->seedDuplicateSwapInvoicePair();

        Artisan::call('agreements:remove-duplicate-swap-invoices', [
            '--agreement' => $duplicate['swap_agreement_id'],
            '--dry-run' => true,
        ]);

        $output = Artisan::output();
        $this->assertStringContainsString((string) $duplicate['swap_invoice_id'], $output);
        $this->assertDatabaseHas('invoices', ['id' => $duplicate['swap_invoice_id']]);
    }

    public function test_force_deletes_duplicate_swap_invoice(): void
    {
        $duplicate = $this->seedDuplicateSwapInvoicePair();

        Artisan::call('agreements:remove-duplicate-swap-invoices', [
            '--agreement' => $duplicate['swap_agreement_id'],
            '--force' => true,
        ]);

        $this->assertDatabaseMissing('invoices', ['id' => $duplicate['swap_invoice_id']]);
        $this->assertDatabaseHas('invoices', ['id' => $duplicate['old_invoice_id']]);
    }

    /**
     * @return array{
     *     swap_agreement_id: int,
     *     swap_invoice_id: int,
     *     old_invoice_id: int
     * }
     */
    private function seedDuplicateSwapInvoicePair(): array
    {
        app(AgreementInvoiceService::class)->generateForAgreement(
            $this->agreement->fresh(['status']),
            Carbon::parse('2026-06-24')
        );

        $oldInvoice = Invoice::query()
            ->where('source_id', $this->agreement->id)
            ->where('invoice_type', 'agreement')
            ->whereDate('invoice_date', '2026-06-24')
            ->firstOrFail();

        $oldInvoice->forceFill([
            'paid_amount' => $oldInvoice->total_amount,
            'balance_amount' => 0,
            'status' => 'paid',
        ])->save();

        $swapAgreement = Agreement::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $this->newCar->id,
            'start_date' => Carbon::parse('2026-06-24 10:00:00'),
            'end_date' => Carbon::parse('2027-06-17'),
            'agreed_rent' => 300,
            'rent_interval' => 'weekly',
            'deposit_amount' => 500,
            'collection_type' => 'manual',
            'using_own_insurance' => false,
            'status_id' => $this->swapStatus->id,
            'upgraded_from_agreement_id' => $this->agreement->id,
            'createdBy' => 1,
            'updatedBy' => 1,
        ]);

        $swapInvoice = Invoice::create([
            'invoice_no' => 'SWAP-DUP-'.uniqid(),
            'driver_id' => $this->driver->id,
            'source_id' => $swapAgreement->id,
            'invoice_type' => 'agreement',
            'invoice_date' => '2026-06-24',
            'due_date' => '2026-06-29',
            'subtotal' => 300,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 300,
            'paid_amount' => 0,
            'balance_amount' => 300,
            'status' => 'pending',
            'notes' => 'Auto-generated agreement invoice',
        ]);

        return [
            'swap_agreement_id' => $swapAgreement->id,
            'swap_invoice_id' => $swapInvoice->id,
            'old_invoice_id' => $oldInvoice->id,
        ];
    }

    private function createCompliantCar(string $registration, int $carModelId, int $counselId, string $fleetStatus): Car
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

        return $car->fresh(['mots', 'roadTaxes', 'phvs', 'reservations']);
    }
}
