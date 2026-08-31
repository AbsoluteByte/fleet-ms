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
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SetupAgreementChangeCarDatabase;
use Tests\TestCase;

class AgreementChangeCarTest extends TestCase
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

        Carbon::setTestNow(Carbon::parse('2026-06-18 10:00:00'));

        $this->tenant = Tenant::create(['company_name' => 'Test Tenant']);
        $this->company = Company::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Company',
        ]);

        $this->driver = Driver::create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Test',
            'last_name' => 'Driver',
            'email' => 'driver@example.com',
            'phone_number' => '07000000000',
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

    public function test_swap_create_carries_deposit_and_creates_no_invoices(): void
    {
        $newAgreement = app(AgreementUpgradeService::class)->createSwapFromAgreement($this->agreement, [
            'car_id' => $this->newCar->id,
            'driver_id' => $this->driver->id,
            'agreed_rent' => 300,
        ]);

        $this->assertTrue($newAgreement->isSwapAgreement());
        $this->assertSame('500.00', $newAgreement->deposit_amount);
        $this->assertSame($this->agreement->id, $newAgreement->upgraded_from_agreement_id);
        $this->assertDatabaseMissing('invoices', [
            'source_id' => $newAgreement->id,
        ]);
        $this->assertSame('Terminated', $this->agreement->fresh(['status'])->status->name);
        $this->assertSame('Closed due to vehicle swap.', $this->agreement->fresh()->termination_notes);
    }

    public function test_next_original_billing_date_invoices_new_rent_only(): void
    {
        $newAgreement = app(AgreementUpgradeService::class)->createSwapFromAgreement($this->agreement, [
            'car_id' => $this->newCar->id,
            'driver_id' => $this->driver->id,
            'agreed_rent' => 300,
        ]);

        $this->assertSame(0, app(AgreementInvoiceService::class)->generateForAgreement(
            $newAgreement->fresh(['upgradedFromAgreement', 'status']),
            Carbon::parse('2026-06-18')
        ));

        $this->assertDatabaseMissing('invoices', [
            'source_id' => $newAgreement->id,
            'invoice_type' => 'agreement',
        ]);

        app(AgreementInvoiceService::class)->generateForAgreement(
            $newAgreement->fresh(['upgradedFromAgreement', 'status']),
            Carbon::parse('2026-06-24')
        );

        $nextAnchorInvoice = Invoice::query()
            ->where('source_id', $newAgreement->id)
            ->where('invoice_type', 'agreement')
            ->whereDate('invoice_date', '2026-06-24')
            ->first();

        $this->assertNotNull($nextAnchorInvoice);
        $this->assertSame('300.00', $nextAnchorInvoice->subtotal);
        $this->assertSame(0.0, (float) $nextAnchorInvoice->discount_amount);
        $this->assertSame(1, Invoice::query()->where('source_id', $newAgreement->id)->count());
    }

    public function test_future_invoices_follow_old_agreement_billing_anchor(): void
    {
        $newAgreement = app(AgreementUpgradeService::class)->createSwapFromAgreement($this->agreement, [
            'car_id' => $this->newCar->id,
            'driver_id' => $this->driver->id,
            'agreed_rent' => 200,
        ]);

        $invoiceService = app(AgreementInvoiceService::class);
        $nextAnchor = $invoiceService->nextBillingAnchor(
            Carbon::parse('2026-06-17'),
            Carbon::parse('2026-06-18'),
            'weekly'
        );

        $this->assertTrue($nextAnchor->eq(Carbon::parse('2026-06-24')));

        app(AgreementInvoiceService::class)->generateForAgreement(
            $newAgreement->fresh(['upgradedFromAgreement', 'status']),
            Carbon::parse('2026-06-24')
        );

        $anchorDates = Invoice::query()
            ->where('source_id', $newAgreement->id)
            ->where('invoice_type', 'agreement')
            ->orderBy('invoice_date')
            ->pluck('invoice_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->all();

        $this->assertContains('2026-06-24', $anchorDates);
        $this->assertNotContains('2026-06-18', $anchorDates);
    }

    public function test_old_agreement_is_terminated_and_deposit_carried_to_new_agreement(): void
    {
        $newAgreement = app(AgreementUpgradeService::class)->createSwapFromAgreement($this->agreement, [
            'car_id' => $this->newCar->id,
            'driver_id' => $this->driver->id,
            'agreed_rent' => 250,
        ]);

        $old = $this->agreement->fresh(['status']);

        $this->assertSame('Terminated', $old->status->name);
        $this->assertSame('2026-06-18', $old->end_date->toDateString());
        $this->assertSame($this->agreement->id, $newAgreement->upgraded_from_agreement_id);
        $this->assertSame('500.00', $newAgreement->deposit_amount);
        $this->assertSame($this->newCar->id, $newAgreement->car_id);
        $this->assertSame('Swap', $newAgreement->fresh('status')->status->name);
    }

    public function test_pending_one_time_discount_transfers_and_applies_on_next_anchor(): void
    {
        $startedAt = Carbon::parse('2026-06-10 09:00:00');
        $this->agreement->update([
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'discount_notes' => 'Welcome discount',
            'discount_is_one_time' => true,
            'discount_started_at' => $startedAt,
        ]);

        $newAgreement = app(AgreementUpgradeService::class)->createSwapFromAgreement($this->agreement->fresh(), [
            'car_id' => $this->newCar->id,
            'driver_id' => $this->driver->id,
            'agreed_rent' => 300,
        ]);

        $this->assertTrue($newAgreement->hasPendingOneTimeDiscount());
        $this->assertSame($startedAt->toDateTimeString(), $newAgreement->discount_started_at->toDateTimeString());

        app(AgreementInvoiceService::class)->generateForAgreement(
            $newAgreement->fresh(['upgradedFromAgreement', 'status']),
            Carbon::parse('2026-06-24')
        );

        $anchorInvoice = Invoice::query()
            ->where('source_id', $newAgreement->id)
            ->whereDate('invoice_date', '2026-06-24')
            ->firstOrFail();

        $this->assertSame(30.0, (float) $anchorInvoice->discount_amount);
        $this->assertSame($anchorInvoice->id, $newAgreement->fresh()->discount_consumed_invoice_id);
    }

    public function test_swap_on_billing_anchor_creates_full_rent_invoice_on_swap_agreement(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-24 10:00:00'));

        app(AgreementInvoiceService::class)->generateForAgreement(
            $this->agreement->fresh(['status']),
            Carbon::parse('2026-06-24')
        );

        $oldAnchorInvoice = Invoice::query()
            ->where('source_id', $this->agreement->id)
            ->where('invoice_type', 'agreement')
            ->whereDate('invoice_date', '2026-06-24')
            ->firstOrFail();

        $this->assertSame('200.00', $oldAnchorInvoice->subtotal);
        $this->assertSame(0.0, (float) $oldAnchorInvoice->paid_amount);

        $newAgreement = app(AgreementUpgradeService::class)->createSwapFromAgreement($this->agreement->fresh(), [
            'car_id' => $this->newCar->id,
            'driver_id' => $this->driver->id,
            'agreed_rent' => 300,
            'start_date' => '2026-06-24 10:00:00',
        ]);

        $this->assertDatabaseMissing('invoices', [
            'id' => $oldAnchorInvoice->id,
        ]);

        $swapAnchorInvoice = Invoice::query()
            ->where('source_id', $newAgreement->id)
            ->where('invoice_type', 'agreement')
            ->whereDate('invoice_date', '2026-06-24')
            ->first();

        $this->assertNotNull($swapAnchorInvoice);
        $this->assertSame('300.00', $swapAnchorInvoice->subtotal);
        $this->assertSame(0.0, (float) $swapAnchorInvoice->discount_amount);
        $this->assertSame(1, Invoice::query()
            ->where('source_id', $newAgreement->id)
            ->where('invoice_type', 'agreement')
            ->count());
    }

    public function test_swap_on_billing_anchor_transfers_one_time_discount_to_swap_invoice(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-24 10:00:00'));

        $startedAt = Carbon::parse('2026-06-10 09:00:00');
        $this->agreement->update([
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'discount_notes' => 'Welcome discount',
            'discount_is_one_time' => true,
            'discount_started_at' => $startedAt,
        ]);

        $newAgreement = app(AgreementUpgradeService::class)->createSwapFromAgreement($this->agreement->fresh(), [
            'car_id' => $this->newCar->id,
            'driver_id' => $this->driver->id,
            'agreed_rent' => 300,
            'start_date' => '2026-06-24 10:00:00',
        ]);

        $this->assertFalse($newAgreement->fresh()->hasPendingOneTimeDiscount());

        $swapAnchorInvoice = Invoice::query()
            ->where('source_id', $newAgreement->id)
            ->where('invoice_type', 'agreement')
            ->whereDate('invoice_date', '2026-06-24')
            ->firstOrFail();

        $this->assertSame(30.0, (float) $swapAnchorInvoice->discount_amount);
        $this->assertSame($swapAnchorInvoice->id, $newAgreement->fresh()->discount_consumed_invoice_id);
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
