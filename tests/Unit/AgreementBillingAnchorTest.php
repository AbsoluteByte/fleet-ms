<?php

namespace Tests\Unit;

use App\Models\Agreement;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\Status;
use App\Models\Tenant;
use App\Services\AgreementInvoiceService;
use Carbon\Carbon;
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
