<?php

namespace Tests\Unit;

use App\Http\Controllers\Backend\AgreementController;
use App\Models\Agreement;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\Status;
use App\Models\Tenant;
use App\Services\AgreementInvoiceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ReflectionMethod;
use Tests\Concerns\SetupAgreementChangeCarDatabase;
use Tests\TestCase;

class AgreementBillingAnchorTest extends TestCase
{
    use SetupAgreementChangeCarDatabase;

    private AgreementInvoiceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAgreementChangeCarDatabase();
        $this->service = new AgreementInvoiceService;
    }

    protected function tearDown(): void
    {
        $this->tearDownAgreementChangeCarDatabase();
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_weekly_friday_start_monday_anchor_prorates_then_full_on_anchor(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-20 10:00:00'));

        $agreement = $this->persistAgreement([
            'start_date' => Carbon::parse('2026-06-19'), // Friday
            'billing_anchor_date' => Carbon::parse('2026-06-22'), // Monday
            'end_date' => Carbon::parse('2026-07-31'),
            'agreed_rent' => 700,
            'rent_interval' => 'Weekly',
            'deposit_amount' => 0,
        ]);

        $expectedProration = $this->service->calculateInitialProrationAmount(
            $agreement,
            $agreement->start_date,
            $agreement->billing_anchor_date
        );

        $this->assertSame(300.0, $expectedProration);

        $this->service->generateForAgreement($agreement, Carbon::parse('2026-07-31'));

        $invoices = Invoice::query()
            ->where('source_id', $agreement->id)
            ->where('invoice_type', 'agreement')
            ->orderBy('invoice_date')
            ->get();

        $prorationInvoice = $invoices->first(fn (Invoice $invoice) => $invoice->invoice_date->eq(Carbon::parse('2026-06-19')));
        $anchorInvoice = $invoices->first(fn (Invoice $invoice) => $invoice->invoice_date->eq(Carbon::parse('2026-06-22')));
        $nextWeekInvoice = $invoices->first(fn (Invoice $invoice) => $invoice->invoice_date->eq(Carbon::parse('2026-06-29')));

        $this->assertNotNull($prorationInvoice);
        $this->assertSame(300.0, (float) $prorationInvoice->total_amount);
        $this->assertStringContainsString('Initial proration', (string) $prorationInvoice->notes);
        $this->assertNotNull($anchorInvoice);
        $this->assertSame(700.0, (float) $anchorInvoice->total_amount);
        $this->assertNotNull($nextWeekInvoice);
        $this->assertFalse($invoices->contains(fn (Invoice $invoice) => $invoice->invoice_date->eq(Carbon::parse('2026-06-26'))));
    }

    public function test_monthly_25th_to_5th_proration_uses_month_length_period(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-25 10:00:00'));

        $agreement = $this->makeAgreement([
            'start_date' => Carbon::parse('2026-01-25'),
            'billing_anchor_date' => Carbon::parse('2026-02-05'),
            'end_date' => Carbon::parse('2026-12-31'),
            'agreed_rent' => 310,
            'rent_interval' => 'Monthly',
        ]);

        $amount = $this->service->calculateInitialProrationAmount(
            $agreement,
            $agreement->start_date,
            $agreement->billing_anchor_date
        );

        // 11 days partial / 31-day January period * £310
        $this->assertSame(110.0, $amount);
    }

    public function test_no_anchor_generates_full_invoice_on_start_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 10:00:00'));

        $agreement = $this->persistAgreement([
            'start_date' => Carbon::parse('2026-06-19'),
            'billing_anchor_date' => null,
            'end_date' => Carbon::parse('2026-07-31'),
            'agreed_rent' => 500,
            'rent_interval' => 'Weekly',
            'deposit_amount' => 0,
        ]);

        $this->assertFalse($agreement->hasDeferredBillingAnchor());

        $this->service->generateForAgreement($agreement, Carbon::parse('2026-06-26'));

        $invoices = Invoice::query()
            ->where('source_id', $agreement->id)
            ->where('invoice_type', 'agreement')
            ->orderBy('invoice_date')
            ->get();

        $this->assertCount(2, $invoices);
        $this->assertTrue($invoices[0]->invoice_date->eq(Carbon::parse('2026-06-19')));
        $this->assertSame(500.0, (float) $invoices[0]->total_amount);
        $this->assertTrue($invoices[1]->invoice_date->eq(Carbon::parse('2026-06-26')));
    }

    public function test_non_active_agreement_does_not_generate_invoices_with_future_end_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 10:00:00'));

        $agreement = $this->persistAgreement([
            'start_date' => Carbon::parse('2026-06-19'),
            'end_date' => Carbon::parse('2027-06-19'),
            'agreed_rent' => 500,
            'rent_interval' => 'Weekly',
            'deposit_amount' => 250,
        ]);
        $expiredStatus = Status::create(['name' => 'Expired', 'type' => 'agreement']);
        $agreement->update(['status_id' => $expiredStatus->id]);
        $agreement->unsetRelation('status');

        $generated = $this->service->generateForAgreement(
            $agreement,
            Carbon::parse('2026-07-31')
        );

        $this->assertSame(0, $generated);
        $this->assertFalse(
            Agreement::query()->billable()->whereKey($agreement->id)->exists()
        );
        $this->assertFalse(
            Invoice::query()->where('source_id', $agreement->id)->exists()
        );
    }

    public function test_anchor_on_same_day_as_start_is_not_deferred(): void
    {
        $agreement = $this->makeAgreement([
            'start_date' => Carbon::parse('2026-06-19 14:30:00'),
            'billing_anchor_date' => Carbon::parse('2026-06-19'),
            'agreed_rent' => 400,
            'rent_interval' => 'Weekly',
        ]);

        $this->assertFalse($agreement->hasDeferredBillingAnchor());
        $this->assertSame(0.0, $this->service->calculateInitialProrationAmount(
            $agreement,
            $agreement->start_date,
            $agreement->billingAnchorDate()
        ));
    }

    public function test_has_deferred_billing_anchor_helper(): void
    {
        $deferred = $this->makeAgreement([
            'start_date' => Carbon::parse('2026-06-19'),
            'billing_anchor_date' => Carbon::parse('2026-06-22'),
        ]);

        $this->assertTrue($deferred->hasDeferredBillingAnchor());
        $this->assertTrue($deferred->billingAnchorDate()->eq(Carbon::parse('2026-06-22')));
    }

    public function test_recurring_discount_remains_on_every_generated_rent_invoice(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 10:00:00'));
        $agreement = $this->persistAgreement([
            'start_date' => Carbon::parse('2026-06-19'),
            'end_date' => Carbon::parse('2026-07-31'),
            'agreed_rent' => 500,
            'rent_interval' => 'Weekly',
            'deposit_amount' => 0,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'discount_is_one_time' => false,
            'discount_started_at' => now(),
        ]);

        $this->assertSame(3, $this->service->generateForAgreement($agreement, Carbon::parse('2026-07-03')));

        $invoices = Invoice::query()->where('source_id', $agreement->id)->orderBy('invoice_date')->get();
        $this->assertCount(3, $invoices);
        $this->assertSame([50.0, 50.0, 50.0], $invoices->map(fn (Invoice $invoice) => (float) $invoice->discount_amount)->all());
        $this->assertSame([450.0, 450.0, 450.0], $invoices->map(fn (Invoice $invoice) => (float) $invoice->total_amount)->all());
        $this->assertSame(450.0, $agreement->discounted_rent);
        $this->assertNull($agreement->fresh()->discount_consumed_invoice_id);
    }

    public function test_one_time_discount_is_consumed_by_exactly_first_new_invoice_and_rerun_is_idempotent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 10:00:00'));
        $agreement = $this->persistAgreement([
            'start_date' => Carbon::parse('2026-06-19'),
            'end_date' => Carbon::parse('2026-07-31'),
            'agreed_rent' => 500,
            'rent_interval' => 'Weekly',
            'deposit_amount' => 0,
            'discount_type' => 'fixed',
            'discount_value' => 75,
            'discount_is_one_time' => true,
            'discount_started_at' => now(),
        ]);

        $this->assertSame(3, $this->service->generateForAgreement($agreement, Carbon::parse('2026-07-03')));

        $invoices = Invoice::query()->where('source_id', $agreement->id)->orderBy('invoice_date')->get();
        $this->assertSame([75.0, 0.0, 0.0], $invoices->map(fn (Invoice $invoice) => (float) $invoice->discount_amount)->all());

        $fresh = $agreement->fresh();
        $this->assertSame($invoices->first()->id, $fresh->discount_consumed_invoice_id);
        $this->assertNotNull($fresh->discount_consumed_at);
        $this->assertSame(0, $this->service->generateForAgreement($fresh, Carbon::parse('2026-07-03')));
        $this->assertDatabaseCount('invoices', 3);
    }

    public function test_initial_proration_consumes_one_time_discount_and_preserves_gross_subtotal(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-19 10:00:00'));
        $agreement = $this->persistAgreement([
            'start_date' => Carbon::parse('2026-06-19'),
            'billing_anchor_date' => Carbon::parse('2026-06-22'),
            'end_date' => Carbon::parse('2026-07-31'),
            'agreed_rent' => 700,
            'rent_interval' => 'Weekly',
            'deposit_amount' => 0,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'discount_is_one_time' => true,
            'discount_started_at' => now(),
        ]);

        $this->service->generateForAgreement($agreement, Carbon::parse('2026-06-22'));

        $invoices = Invoice::query()->where('source_id', $agreement->id)->orderBy('invoice_date')->get();
        $this->assertCount(2, $invoices);
        $this->assertSame(300.0, (float) $invoices[0]->subtotal);
        $this->assertSame(30.0, (float) $invoices[0]->discount_amount);
        $this->assertSame(270.0, (float) $invoices[0]->total_amount);
        $this->assertSame(0.0, (float) $invoices[1]->discount_amount);
        $this->assertSame($invoices[0]->id, $agreement->fresh()->discount_consumed_invoice_id);
    }

    public function test_closure_recalculates_only_consuming_invoice_and_same_day_removal_releases_discount(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-22 10:00:00'));
        $agreement = $this->persistAgreement([
            'start_date' => Carbon::parse('2026-06-22'),
            'end_date' => Carbon::parse('2026-07-31'),
            'agreed_rent' => 700,
            'rent_interval' => 'Weekly',
            'deposit_amount' => 0,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'discount_is_one_time' => true,
            'discount_started_at' => now(),
        ]);

        $this->service->generateForAgreement($agreement, Carbon::parse('2026-06-22'));
        $invoice = Invoice::query()->where('source_id', $agreement->id)->firstOrFail();

        $this->service->reconcileFinalInvoice($agreement, Carbon::parse('2026-06-24'));
        $invoice->refresh();
        $this->assertSame(200.0, (float) $invoice->subtotal);
        $this->assertSame(20.0, (float) $invoice->discount_amount);
        $this->assertSame(180.0, (float) $invoice->total_amount);

        $this->service->reconcileFinalInvoice($agreement, Carbon::parse('2026-06-22'));
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
        $this->assertTrue($agreement->fresh()->hasPendingOneTimeDiscount());
    }

    public function test_discount_configuration_preserves_start_date_until_it_changes_or_is_removed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-18 10:00:00'));
        Auth::shouldReceive('user')->andReturn((object) ['email' => 'jawad@samoretraders.com']);

        $existing = new Agreement([
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'discount_notes' => 'Loyalty',
            'discount_is_one_time' => true,
            'discount_started_at' => Carbon::parse('2026-06-01 09:00:00'),
            'discount_consumed_at' => Carbon::parse('2026-06-08 09:00:00'),
            'discount_consumed_invoice_id' => 99,
        ]);
        $method = new ReflectionMethod(AgreementController::class, 'applyDiscountData');
        $method->setAccessible(true);
        $controller = app(AgreementController::class);

        $unchanged = $method->invoke($controller, [], Request::create('/', 'POST', [
            'discount_type' => 'percentage',
            'discount_value' => '10.00',
            'discount_notes' => 'Loyalty',
            'discount_is_one_time' => '1',
        ]), $existing);
        $this->assertSame('2026-06-01 09:00:00', $unchanged['discount_started_at']->toDateTimeString());
        $this->assertSame(99, $unchanged['discount_consumed_invoice_id']);

        $changed = $method->invoke($controller, [], Request::create('/', 'POST', [
            'discount_type' => 'percentage',
            'discount_value' => '15',
            'discount_notes' => 'Loyalty',
            'discount_is_one_time' => '1',
        ]), $existing);
        $this->assertSame(now()->toDateTimeString(), $changed['discount_started_at']->toDateTimeString());
        $this->assertNull($changed['discount_consumed_at']);
        $this->assertNull($changed['discount_consumed_invoice_id']);

        $removed = $method->invoke(
            $controller,
            [],
            Request::create('/', 'POST', ['discount_is_one_time' => '0']),
            $existing
        );
        $this->assertNull($removed['discount_type']);
        $this->assertFalse($removed['discount_is_one_time']);
        $this->assertNull($removed['discount_started_at']);
    }

    public function test_changing_start_date_removes_unpaid_stale_invoices(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-24 10:00:00'));

        $agreement = $this->persistAgreement([
            'start_date' => Carbon::parse('2026-05-20'), // Wednesday
            'billing_anchor_date' => null,
            'end_date' => Carbon::parse('2027-05-20'),
            'agreed_rent' => 210,
            'rent_interval' => 'Weekly',
            'deposit_amount' => 0,
        ]);

        $this->service->generateForAgreement($agreement, Carbon::parse('2026-07-24'));

        $wednesdayDates = Invoice::query()
            ->where('source_id', $agreement->id)
            ->where('invoice_type', 'agreement')
            ->orderBy('invoice_date')
            ->pluck('invoice_date')
            ->map(fn ($date) => $date->toDateString())
            ->all();

        $this->assertContains('2026-05-20', $wednesdayDates);
        $this->assertContains('2026-07-22', $wednesdayDates);
        $this->assertGreaterThanOrEqual(2, count($wednesdayDates));

        $agreement->update(['start_date' => Carbon::parse('2026-05-22')]); // Friday
        $agreement->refresh();

        $this->service->reconcileBillingScheduleInvoices($agreement);
        $this->service->generateForAgreement($agreement, Carbon::parse('2026-07-24'));

        $remainingDates = Invoice::query()
            ->where('source_id', $agreement->id)
            ->where('invoice_type', 'agreement')
            ->orderBy('invoice_date')
            ->pluck('invoice_date')
            ->map(fn ($date) => $date->toDateString())
            ->all();

        $this->assertNotContains('2026-05-20', $remainingDates);
        $this->assertNotContains('2026-07-22', $remainingDates);
        $this->assertContains('2026-05-22', $remainingDates);
        $this->assertContains('2026-07-24', $remainingDates);
        $this->assertSame(
            Carbon::parse('2026-05-22')->dayOfWeek,
            Carbon::parse($remainingDates[0])->dayOfWeek
        );
    }

    public function test_changing_billing_anchor_removes_stale_unpaid_invoices(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-24 10:00:00'));

        $agreement = $this->persistAgreement([
            'start_date' => Carbon::parse('2026-06-19'), // Friday
            'billing_anchor_date' => null,
            'end_date' => Carbon::parse('2027-06-19'),
            'agreed_rent' => 500,
            'rent_interval' => 'Weekly',
            'deposit_amount' => 0,
        ]);

        $this->service->generateForAgreement($agreement, Carbon::parse('2026-07-24'));

        $this->assertTrue(Invoice::query()
            ->where('source_id', $agreement->id)
            ->whereDate('invoice_date', '2026-06-19')
            ->exists());

        $agreement->update(['billing_anchor_date' => Carbon::parse('2026-06-22')]); // Monday anchor
        $agreement->refresh();

        $this->service->reconcileBillingScheduleInvoices($agreement);
        $this->service->generateForAgreement($agreement, Carbon::parse('2026-07-24'));

        $remainingDates = Invoice::query()
            ->where('source_id', $agreement->id)
            ->where('invoice_type', 'agreement')
            ->orderBy('invoice_date')
            ->pluck('invoice_date')
            ->map(fn ($date) => $date->toDateString())
            ->all();

        $this->assertNotContains('2026-06-26', $remainingDates);
        $this->assertContains('2026-06-19', $remainingDates);
        $this->assertContains('2026-06-22', $remainingDates);
        $this->assertContains('2026-06-29', $remainingDates);
    }

    public function test_paid_stale_invoices_are_not_deleted_on_schedule_change(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-24 10:00:00'));

        $agreement = $this->persistAgreement([
            'start_date' => Carbon::parse('2026-05-20'),
            'billing_anchor_date' => null,
            'end_date' => Carbon::parse('2027-05-20'),
            'agreed_rent' => 210,
            'rent_interval' => 'Weekly',
            'deposit_amount' => 0,
        ]);

        $this->service->generateForAgreement($agreement, Carbon::parse('2026-05-27'));

        $paidInvoice = Invoice::query()
            ->where('source_id', $agreement->id)
            ->whereDate('invoice_date', '2026-05-20')
            ->firstOrFail();
        $paidInvoice->update([
            'paid_amount' => 210,
            'balance_amount' => 0,
            'status' => 'paid',
        ]);

        $agreement->update(['start_date' => Carbon::parse('2026-05-22')]);
        $agreement->refresh();

        $this->service->reconcileBillingScheduleInvoices($agreement);
        $this->service->generateForAgreement($agreement, Carbon::parse('2026-07-24'));

        $this->assertDatabaseHas('invoices', ['id' => $paidInvoice->id]);
        $this->assertFalse(Invoice::query()
            ->where('source_id', $agreement->id)
            ->whereDate('invoice_date', '2026-05-27')
            ->exists());
        $this->assertTrue(Invoice::query()
            ->where('source_id', $agreement->id)
            ->whereDate('invoice_date', '2026-05-22')
            ->exists());
    }

    private function makeAgreement(array $attributes): Agreement
    {
        return new Agreement(array_merge([
            'discount_type' => null,
            'discount_value' => null,
            'end_date' => Carbon::parse('2027-06-17'),
            'billing_anchor_date' => null,
        ], $attributes));
    }

    private function persistAgreement(array $attributes): Agreement
    {
        $tenant = Tenant::create(['company_name' => 'Billing Anchor Tenant']);
        $driver = Driver::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Anchor',
            'last_name' => 'Driver',
            'email' => 'anchor-'.uniqid().'@example.com',
            'phone_number' => '07000000001',
        ]);
        $status = Status::create(['name' => 'Active', 'type' => 'agreement']);

        return Agreement::create(array_merge([
            'tenant_id' => $tenant->id,
            'company_id' => null,
            'driver_id' => $driver->id,
            'car_id' => null,
            'status_id' => $status->id,
            'collection_type' => 'weekly',
            'auto_schedule_collections' => false,
            'discount_type' => null,
            'discount_value' => null,
        ], $attributes));
    }
}
