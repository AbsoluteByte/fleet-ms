<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\SetupAgreementChangeCarDatabase;
use Tests\TestCase;

class InvoiceReportTest extends TestCase
{
    use SetupAgreementChangeCarDatabase;

    private Tenant $tenant;

    private User $user;

    private Driver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAgreementChangeCarDatabase();
        $this->setUpHttpTestExtras();

        Carbon::setTestNow(Carbon::parse('2026-08-17 10:00:00'));

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Invoice Report Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $this->driver = Driver::query()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Ali',
            'last_name' => 'Khan',
            'email' => 'ali-report@example.com',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create(['email' => 'invoice-report@example.com']);
        $this->user->tenants()->attach($this->tenant->id, [
            'role' => 'admin',
            'is_primary' => true,
            'joined_at' => now(),
        ]);
        $roleId = DB::table('roles')->insertGetId([
            'name' => 'admin',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('model_has_roles')->insert([
            'role_id' => $roleId,
            'model_type' => User::class,
            'model_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);
        $this->user->switchTenant($this->tenant->id);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        $this->tearDownAgreementChangeCarDatabase();

        parent::tearDown();
    }

    public function test_page_loads_with_heading_and_export_controls(): void
    {
        $response = $this->get(route('payments.invoices'));

        $response->assertOk();
        $response->assertSee('Invoices');
        $response->assertSee('Export CSV');
        $response->assertSee('Export PDF');
        $response->assertSee('Invoices generated');
        $response->assertSee('Outstanding still to collect');
    }

    public function test_date_range_includes_invoices_in_range_and_excludes_those_outside(): void
    {
        $this->createInvoice([
            'invoice_no' => 'INV-IN-RANGE',
            'invoice_date' => '2026-08-10',
            'total_amount' => 100,
            'status' => 'pending',
        ]);
        $this->createInvoice([
            'invoice_no' => 'INV-OUT-RANGE',
            'invoice_date' => '2026-07-20',
            'total_amount' => 50,
            'status' => 'pending',
        ]);

        $response = $this->get(route('payments.invoices', [
            'from' => '2026-08-01',
            'to' => '2026-08-31',
        ]));

        $response->assertOk();
        $invoiceNos = collect($response->viewData('rows'))->pluck('invoice_no');
        $this->assertTrue($invoiceNos->contains('INV-IN-RANGE'));
        $this->assertFalse($invoiceNos->contains('INV-OUT-RANGE'));
        $response->assertSee('INV-IN-RANGE');
        $response->assertDontSee('INV-OUT-RANGE');
    }

    public function test_default_date_range_is_current_month_to_today(): void
    {
        $this->createInvoice([
            'invoice_no' => 'INV-THIS-MONTH',
            'invoice_date' => '2026-08-05',
            'total_amount' => 80,
            'status' => 'pending',
        ]);
        $this->createInvoice([
            'invoice_no' => 'INV-LAST-MONTH',
            'invoice_date' => '2026-07-31',
            'total_amount' => 40,
            'status' => 'pending',
        ]);

        $response = $this->get(route('payments.invoices'));

        $response->assertOk();
        $this->assertSame('2026-08-01', $response->viewData('from'));
        $this->assertSame('2026-08-17', $response->viewData('to'));

        $invoiceNos = collect($response->viewData('rows'))->pluck('invoice_no');
        $this->assertTrue($invoiceNos->contains('INV-THIS-MONTH'));
        $this->assertFalse($invoiceNos->contains('INV-LAST-MONTH'));
    }

    public function test_tenant_isolation_hides_other_tenant_invoices(): void
    {
        $otherTenant = Tenant::query()->create([
            'company_name' => 'Other Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $otherDriver = Driver::query()->create([
            'tenant_id' => $otherTenant->id,
            'first_name' => 'Other',
            'last_name' => 'Driver',
            'email' => 'other-report@example.com',
            'is_active' => true,
        ]);

        $this->createInvoice([
            'invoice_no' => 'INV-OWN',
            'invoice_date' => '2026-08-10',
            'total_amount' => 100,
            'status' => 'pending',
        ]);
        $this->createInvoice([
            'driver_id' => $otherDriver->id,
            'invoice_no' => 'INV-OTHER-TENANT',
            'invoice_date' => '2026-08-10',
            'total_amount' => 999,
            'status' => 'pending',
        ]);

        $response = $this->get(route('payments.invoices', [
            'from' => '2026-08-01',
            'to' => '2026-08-31',
        ]));

        $response->assertOk();
        $invoiceNos = collect($response->viewData('rows'))->pluck('invoice_no');
        $this->assertTrue($invoiceNos->contains('INV-OWN'));
        $this->assertFalse($invoiceNos->contains('INV-OTHER-TENANT'));
        $this->assertSame(1, $response->viewData('summary')['generated_count']);
        $this->assertEquals(100.0, $response->viewData('summary')['generated_total']);
    }

    public function test_summary_counts_and_totals_match_date_range_invoices(): void
    {
        $this->createInvoice([
            'invoice_no' => 'INV-PAID',
            'invoice_date' => '2026-08-02',
            'total_amount' => 200,
            'paid_amount' => 200,
            'balance_amount' => 0,
            'status' => 'paid',
        ]);
        $this->createInvoice([
            'invoice_no' => 'INV-PENDING',
            'invoice_date' => '2026-08-03',
            'total_amount' => 150,
            'status' => 'pending',
        ]);
        $this->createInvoice([
            'invoice_no' => 'INV-OVERDUE',
            'invoice_date' => '2026-08-04',
            'total_amount' => 50,
            'status' => 'overdue',
        ]);
        $this->createInvoice([
            'invoice_no' => 'INV-PARTIAL',
            'invoice_date' => '2026-08-05',
            'total_amount' => 100,
            'paid_amount' => 40,
            'balance_amount' => 60,
            'status' => 'partial',
        ]);
        $this->createInvoice([
            'invoice_no' => 'INV-CANCELLED',
            'invoice_date' => '2026-08-06',
            'total_amount' => 500,
            'status' => 'cancelled',
        ]);
        $this->createInvoice([
            'invoice_no' => 'INV-OUTSIDE',
            'invoice_date' => '2026-07-01',
            'total_amount' => 80,
            'status' => 'pending',
        ]);

        $response = $this->get(route('payments.invoices', [
            'from' => '2026-08-01',
            'to' => '2026-08-31',
        ]));

        $response->assertOk();
        $summary = $response->viewData('summary');

        $this->assertSame(4, $summary['generated_count']);
        $this->assertEquals(500.0, $summary['generated_total']);
        $this->assertSame(1, $summary['paid_count']);
        $this->assertEquals(200.0, $summary['paid_total']);
        $this->assertSame(2, $summary['pending_count']);
        $this->assertEquals(200.0, $summary['pending_total']);
        $this->assertSame(1, $summary['partial_count']);
        $this->assertEquals(100.0, $summary['partial_total']);
        $this->assertEquals(260.0, $summary['outstanding']);
        $this->assertFalse(collect($response->viewData('rows'))->pluck('invoice_no')->contains('INV-CANCELLED'));
    }

    public function test_status_filter_restricts_table_but_not_summary(): void
    {
        $this->createInvoice([
            'invoice_no' => 'INV-PAID',
            'invoice_date' => '2026-08-02',
            'total_amount' => 200,
            'paid_amount' => 200,
            'balance_amount' => 0,
            'status' => 'paid',
        ]);
        $this->createInvoice([
            'invoice_no' => 'INV-PENDING',
            'invoice_date' => '2026-08-03',
            'total_amount' => 150,
            'status' => 'pending',
        ]);
        $this->createInvoice([
            'invoice_no' => 'INV-OVERDUE',
            'invoice_date' => '2026-08-04',
            'total_amount' => 50,
            'status' => 'overdue',
        ]);
        $this->createInvoice([
            'invoice_no' => 'INV-PARTIAL',
            'invoice_date' => '2026-08-05',
            'total_amount' => 100,
            'paid_amount' => 40,
            'balance_amount' => 60,
            'status' => 'partial',
        ]);

        $paid = $this->get(route('payments.invoices', [
            'from' => '2026-08-01',
            'to' => '2026-08-31',
            'status' => 'paid',
        ]));
        $paidNos = collect($paid->viewData('rows'))->pluck('invoice_no')->all();
        $this->assertSame(['INV-PAID'], $paidNos);
        $this->assertSame(4, $paid->viewData('summary')['generated_count']);
        $this->assertEquals(500.0, $paid->viewData('summary')['generated_total']);

        $pending = $this->get(route('payments.invoices', [
            'from' => '2026-08-01',
            'to' => '2026-08-31',
            'status' => 'pending',
        ]));
        $pendingNos = collect($pending->viewData('rows'))->pluck('invoice_no')->sort()->values()->all();
        $this->assertSame(['INV-OVERDUE', 'INV-PENDING'], $pendingNos);
        $this->assertSame(4, $pending->viewData('summary')['generated_count']);

        $partial = $this->get(route('payments.invoices', [
            'from' => '2026-08-01',
            'to' => '2026-08-31',
            'status' => 'partial',
        ]));
        $this->assertSame(['INV-PARTIAL'], collect($partial->viewData('rows'))->pluck('invoice_no')->all());
        $this->assertSame(4, $partial->viewData('summary')['generated_count']);
    }

    public function test_payment_date_uses_latest_posted_allocation(): void
    {
        $invoice = $this->createInvoice([
            'invoice_no' => 'INV-PAID-DATE',
            'invoice_date' => '2026-08-02',
            'total_amount' => 200,
            'paid_amount' => 200,
            'balance_amount' => 0,
            'status' => 'paid',
        ]);
        $this->createInvoice([
            'invoice_no' => 'INV-UNPAID',
            'invoice_date' => '2026-08-03',
            'total_amount' => 80,
            'status' => 'pending',
        ]);

        $pendingPayment = Payment::query()->create([
            'driver_id' => $this->driver->id,
            'payment_date' => '2026-08-04',
            'amount' => 50,
            'posting_status' => Payment::POSTING_STATUS_PENDING,
        ]);
        $olderPosted = Payment::query()->create([
            'driver_id' => $this->driver->id,
            'payment_date' => '2026-08-05',
            'amount' => 80,
            'posting_status' => Payment::POSTING_STATUS_POSTED,
        ]);
        $latestPosted = Payment::query()->create([
            'driver_id' => $this->driver->id,
            'payment_date' => '2026-08-12',
            'amount' => 120,
            'posting_status' => Payment::POSTING_STATUS_POSTED,
        ]);

        PaymentAllocation::query()->create([
            'payment_id' => $pendingPayment->id,
            'invoice_id' => $invoice->id,
            'allocated_amount' => 50,
        ]);
        PaymentAllocation::query()->create([
            'payment_id' => $olderPosted->id,
            'invoice_id' => $invoice->id,
            'allocated_amount' => 80,
        ]);
        PaymentAllocation::query()->create([
            'payment_id' => $latestPosted->id,
            'invoice_id' => $invoice->id,
            'allocated_amount' => 120,
        ]);

        $response = $this->get(route('payments.invoices', [
            'from' => '2026-08-01',
            'to' => '2026-08-31',
        ]));

        $response->assertOk();
        $rows = collect($response->viewData('rows'))->keyBy('invoice_no');
        $this->assertSame('12 Aug 2026', $rows['INV-PAID-DATE']['payment_date']);
        $this->assertSame('—', $rows['INV-UNPAID']['payment_date']);
        $this->assertSame($this->driver->fresh()->selectOptionLabel(), $rows['INV-PAID-DATE']['customer']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createInvoice(array $overrides): Invoice
    {
        $total = (float) ($overrides['total_amount'] ?? 100);

        return Invoice::query()->create(array_merge([
            'driver_id' => $this->driver->id,
            'invoice_type' => 'agreement',
            'source_id' => 1,
            'invoice_date' => '2026-08-10',
            'due_date' => '2026-08-10',
            'subtotal' => $total,
            'total_amount' => $total,
            'paid_amount' => 0,
            'balance_amount' => $total,
            'status' => 'pending',
        ], $overrides));
    }

    private function setUpHttpTestExtras(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->default(1);
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

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
    }
}
