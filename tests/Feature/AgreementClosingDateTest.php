<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Car;
use App\Models\Company;
use App\Models\Driver;
use App\Models\DriverCreditTransaction;
use App\Models\DriverCreditTransactionLine;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Status;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\Concerns\SetupAgreementChangeCarDatabase;
use Tests\TestCase;

class AgreementClosingDateTest extends TestCase
{
    use SetupAgreementChangeCarDatabase;

    private Tenant $tenant;

    private Company $company;

    private Driver $driver;

    private Car $car;

    private Status $activeStatus;

    private Status $expiredStatus;

    private Status $terminatedStatus;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpAgreementChangeCarDatabase();
        $this->setUpHttpTestExtras();

        Carbon::setTestNow(Carbon::parse('2026-06-20 10:00:00'));

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Closing Date Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $this->company = Company::query()->create(['tenant_id' => $this->tenant->id, 'name' => 'Co']);
        $this->driver = Driver::query()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane-closing@example.com',
            'phone_number' => '07000000002',
        ]);

        $carModelId = (int) DB::table('car_models')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $counselId = (int) DB::table('counsels')->insertGetId([
            'name' => 'Test Counsel',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->car = $this->createCompliantCar($this->tenant->id, $this->company->id, $carModelId, $counselId);
        $this->activeStatus = Status::query()->create(['name' => 'Active', 'type' => 'agreement']);
        $this->expiredStatus = Status::query()->create(['name' => 'Expired', 'type' => 'agreement']);
        $this->terminatedStatus = Status::query()->create(['name' => 'Terminated', 'type' => 'agreement']);

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
        Carbon::setTestNow();
        Schema::dropIfExists('driver_credit_transaction_lines');
        Schema::dropIfExists('driver_credit_transactions');
        Schema::dropIfExists('agreement_collections');
        Schema::dropIfExists('car_status_histories');
        Schema::dropIfExists('tenant_user');
        $this->tearDownAgreementChangeCarDatabase();

        parent::tearDown();
    }

    public function test_terminated_status_requires_closing_date(): void
    {
        $agreement = $this->createActiveAgreement();

        $response = $this->from(route('agreements.edit', $agreement))
            ->put(route('agreements.update', $agreement), $this->basePayload([
                'status_id' => $this->terminatedStatus->id,
            ]));

        $response->assertSessionHasErrors('closing_date');
    }

    public function test_expired_status_saves_closing_date(): void
    {
        $agreement = $this->createActiveAgreement();

        $response = $this->from(route('agreements.edit', $agreement))
            ->put(route('agreements.update', $agreement), $this->basePayload([
                'status_id' => $this->expiredStatus->id,
                'closing_date' => '2026-06-20T16:45',
            ]));

        $this->assertNull($response->getSession()->get('error'));
        $response->assertRedirect(route('agreements.index'));
        $response->assertSessionHasNoErrors();

        $agreement->refresh();
        $this->assertSame($this->expiredStatus->id, $agreement->status_id);
        $this->assertSame('2026-06-20 16:45:00', $agreement->closing_date->format('Y-m-d H:i:s'));
    }

    public function test_reactivating_agreement_clears_closing_date(): void
    {
        $agreement = $this->createActiveAgreement();
        $agreement->update([
            'status_id' => $this->expiredStatus->id,
            'closing_date' => '2026-06-18 09:00:00',
        ]);

        $response = $this->from(route('agreements.edit', $agreement))
            ->put(route('agreements.update', $agreement), $this->basePayload([
                'status_id' => $this->activeStatus->id,
            ]));

        $response->assertRedirect(route('agreements.index'));
        $response->assertSessionHasNoErrors();

        $agreement->refresh();
        $this->assertSame($this->activeStatus->id, $agreement->status_id);
        $this->assertNull($agreement->closing_date);
    }

    public function test_terminated_status_with_closing_date_updates_successfully(): void
    {
        $agreement = $this->createActiveAgreement();

        $response = $this->from(route('agreements.edit', $agreement))
            ->put(route('agreements.update', $agreement), $this->basePayload([
                'status_id' => $this->terminatedStatus->id,
                'closing_date' => '2026-06-20T11:00',
            ]));

        $response->assertRedirect(route('agreements.index'));
        $response->assertSessionHasNoErrors();

        $agreement->refresh();
        $this->assertSame($this->terminatedStatus->id, $agreement->status_id);
        $this->assertSame('2026-06-20 11:00:00', $agreement->closing_date->format('Y-m-d H:i:s'));
    }

    public function test_same_day_closure_removes_current_rent_invoice_only(): void
    {
        $agreement = $this->createActiveAgreement();
        $earlierInvoice = $this->createRentInvoice($agreement, '2026-06-08', 150);
        $currentInvoice = $this->createRentInvoice($agreement, '2026-06-15', 150);
        $depositInvoice = $this->createRentInvoice($agreement, '2026-06-01', 200, 'agreement_deposit');

        $response = $this->put(route('agreements.update', $agreement), $this->basePayload([
            'status_id' => $this->terminatedStatus->id,
            'closing_date' => '2026-06-15T12:00',
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('invoices', ['id' => $currentInvoice->id]);
        $this->assertDatabaseHas('invoices', ['id' => $earlierInvoice->id, 'total_amount' => 150]);
        $this->assertDatabaseHas('invoices', ['id' => $depositInvoice->id, 'total_amount' => 200]);
        $this->assertEquals(350, $this->driver->fresh()->total_due);
    }

    public function test_midweek_closure_prorates_monday_invoice_through_tuesday_with_discount(): void
    {
        $agreement = $this->createActiveAgreement();
        $agreement->update([
            'agreed_rent' => 700,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'discount_notes' => 'Loyalty discount',
        ]);
        $invoice = $this->createRentInvoice($agreement, '2026-06-15', 630, 'agreement', [
            'subtotal' => 700,
            'discount_amount' => 70,
        ]);

        $response = $this->put(route('agreements.update', $agreement), $this->basePayload([
            'agreed_rent' => 700,
            'status_id' => $this->expiredStatus->id,
            'closing_date' => '2026-06-17T09:00',
        ]));

        $response->assertSessionHasNoErrors();
        $invoice->refresh();
        $this->assertEquals(200, (float) $invoice->subtotal);
        $this->assertEquals(20, (float) $invoice->discount_amount);
        $this->assertEquals(180, (float) $invoice->total_amount);
        $this->assertEquals(180, (float) $invoice->balance_amount);
        $this->assertStringContainsString('2 days', (string) $invoice->notes);
    }

    public function test_closure_releases_excess_payment_allocation_as_driver_credit(): void
    {
        $agreement = $this->createActiveAgreement();
        $agreement->update(['agreed_rent' => 700]);
        $invoice = $this->createRentInvoice($agreement, '2026-06-15', 700);
        $payment = Payment::query()->create([
            'driver_id' => $this->driver->id,
            'payment_method' => 'Cash',
            'payment_date' => '2026-06-15',
            'amount' => 250,
            'posting_status' => Payment::POSTING_STATUS_POSTED,
            'auto_allocate' => false,
            'created_by' => $this->user->id,
        ]);
        PaymentAllocation::query()->create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'allocated_amount' => 250,
        ]);
        $invoice->refreshPaymentTotals();

        $response = $this->put(route('agreements.update', $agreement), $this->basePayload([
            'agreed_rent' => 700,
            'status_id' => $this->terminatedStatus->id,
            'closing_date' => '2026-06-16T09:00',
        ]));

        $response->assertSessionHasNoErrors();
        $invoice->refresh();
        $this->assertEquals(100, (float) $invoice->total_amount);
        $this->assertEquals(100, (float) $invoice->paid_amount);
        $this->assertEquals(0, (float) $invoice->balance_amount);
        $this->assertEquals(100, (float) $payment->fresh()->allocated_amount);
        $this->assertEquals(150, $this->driver->fresh()->credit_amount);
    }

    public function test_closure_reverses_posted_credit_excess_and_cancels_pending_reservation(): void
    {
        $agreement = $this->createActiveAgreement();
        $agreement->update(['agreed_rent' => 700]);
        $invoice = $this->createRentInvoice($agreement, '2026-06-15', 700);

        $postedSource = Payment::query()->create([
            'driver_id' => $this->driver->id,
            'payment_method' => 'Cash',
            'payment_date' => '2026-06-01',
            'amount' => 300,
            'posting_status' => Payment::POSTING_STATUS_POSTED,
            'auto_allocate' => false,
            'created_by' => $this->user->id,
        ]);
        $postedTransaction = DriverCreditTransaction::query()->create([
            'tenant_id' => $this->tenant->id,
            'driver_id' => $this->driver->id,
            'kind' => DriverCreditTransaction::KIND_INVOICE_APPLICATION,
            'amount' => 300,
            'request_date' => '2026-06-15',
            'posting_status' => DriverCreditTransaction::STATUS_POSTED,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
            'approved_at' => now(),
        ]);
        $convertedLine = $postedTransaction->lines()->create([
            'source_payment_id' => $postedSource->id,
            'target_invoice_id' => $invoice->id,
            'amount' => 300,
            'status' => DriverCreditTransactionLine::STATUS_CONVERTED,
        ]);
        PaymentAllocation::query()->create([
            'payment_id' => $postedSource->id,
            'invoice_id' => $invoice->id,
            'driver_credit_transaction_line_id' => $convertedLine->id,
            'allocated_amount' => 300,
        ]);
        $invoice->refreshPaymentTotals();

        $reservedSource = Payment::query()->create([
            'driver_id' => $this->driver->id,
            'payment_method' => 'Cash',
            'payment_date' => '2026-06-02',
            'amount' => 100,
            'posting_status' => Payment::POSTING_STATUS_POSTED,
            'auto_allocate' => false,
            'created_by' => $this->user->id,
        ]);
        $pendingTransaction = DriverCreditTransaction::query()->create([
            'tenant_id' => $this->tenant->id,
            'driver_id' => $this->driver->id,
            'kind' => DriverCreditTransaction::KIND_INVOICE_APPLICATION,
            'amount' => 50,
            'request_date' => '2026-06-16',
            'posting_status' => DriverCreditTransaction::STATUS_PENDING,
            'created_by' => $this->user->id,
        ]);
        $pendingTransaction->lines()->create([
            'source_payment_id' => $reservedSource->id,
            'target_invoice_id' => $invoice->id,
            'amount' => 50,
            'status' => DriverCreditTransactionLine::STATUS_RESERVED,
        ]);

        $response = $this->put(route('agreements.update', $agreement), $this->basePayload([
            'agreed_rent' => 700,
            'status_id' => $this->expiredStatus->id,
            'closing_date' => '2026-06-17T09:00',
        ]));

        $response->assertSessionHasNoErrors();
        $invoice->refresh();
        $this->assertEquals(200, (float) $invoice->total_amount);
        $this->assertEquals(200, (float) $invoice->paid_amount);
        $this->assertDatabaseMissing('driver_credit_transactions', ['id' => $pendingTransaction->id]);
        $this->assertDatabaseHas('driver_credit_transaction_lines', [
            'driver_credit_transaction_id' => $postedTransaction->id,
            'target_invoice_id' => null,
            'amount' => 100,
            'status' => DriverCreditTransactionLine::STATUS_REVERSED,
        ]);
        $this->assertEquals(200, $this->driver->fresh()->credit_amount);
    }

    public function test_termination_notice_without_closing_status_does_not_reconcile_invoice(): void
    {
        $agreement = $this->createActiveAgreement();
        $agreement->update(['agreed_rent' => 700]);
        $invoice = $this->createRentInvoice($agreement, '2026-06-15', 700);

        $response = $this->put(route('agreements.update', $agreement), $this->basePayload([
            'agreed_rent' => 700,
            'termination_notice_date' => '2026-06-17',
            'termination_available_from_date' => '2026-06-17',
            'termination_notes' => 'Notice only',
        ]));

        $response->assertSessionHasNoErrors();
        $invoice->refresh();
        $this->assertEquals(700, (float) $invoice->total_amount);
        $this->assertEquals(700, (float) $invoice->balance_amount);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function basePayload(array $overrides = []): array
    {
        return array_merge([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $this->car->id,
            'start_date' => '2026-06-01T09:00',
            'end_date' => '2027-06-01',
            'agreed_rent' => 150,
            'rent_interval' => 'Weekly',
            'collection_type' => 'weekly',
            'deposit_amount' => 200,
            'status_id' => $this->activeStatus->id,
        ], $overrides);
    }

    private function createActiveAgreement(): Agreement
    {
        return Agreement::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $this->car->id,
            'start_date' => '2026-06-01',
            'end_date' => '2027-06-01',
            'agreed_rent' => 150,
            'rent_interval' => 'Weekly',
            'deposit_amount' => 200,
            'collection_type' => 'weekly',
            'status_id' => $this->activeStatus->id,
        ]);
    }

    private function createRentInvoice(
        Agreement $agreement,
        string $invoiceDate,
        float $total,
        string $invoiceType = 'agreement',
        array $overrides = []
    ): Invoice {
        return Invoice::query()->create(array_merge([
            'driver_id' => $agreement->driver_id,
            'source_id' => $agreement->id,
            'invoice_type' => $invoiceType,
            'invoice_date' => $invoiceDate,
            'due_date' => Carbon::parse($invoiceDate)->addDays(5),
            'subtotal' => $total,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => $total,
            'paid_amount' => 0,
            'balance_amount' => $total,
            'status' => 'pending',
            'notes' => 'Test invoice',
        ], $overrides));
    }

    private function setUpHttpTestExtras(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->default(1);
        });

        Schema::table('agreements', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_agreement_id')->nullable();
            $table->text('mutual_detail_slip_document')->nullable();
        });

        Schema::table('car_reservations', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable();
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

        Schema::create('car_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('car_id');
            $table->string('previous_status')->nullable();
            $table->string('new_status');
            $table->json('status_data')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamps();
        });

        Schema::create('agreement_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id');
            $table->date('date');
            $table->date('due_date')->nullable();
            $table->string('method');
            $table->decimal('amount', 10, 2);
            $table->string('payment_status')->default('pending');
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_auto_generated')->default(false);
            $table->timestamps();
        });

        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->unsignedBigInteger('driver_credit_transaction_line_id')->nullable();
        });

        Schema::create('driver_credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('driver_id');
            $table->string('kind', 30);
            $table->decimal('amount', 12, 2);
            $table->date('request_date');
            $table->string('payment_method')->nullable();
            $table->foreignId('bank_account_id')->nullable();
            $table->string('posting_status', 20)->default('pending');
            $table->foreignId('created_by')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('driver_credit_transaction_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_credit_transaction_id');
            $table->foreignId('source_payment_id');
            $table->foreignId('target_invoice_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('reserved');
            $table->timestamps();
        });
    }

    private function createCompliantCar(int $tenantId, int $companyId, int $carModelId, int $counselId): Car
    {
        $car = Car::query()->create([
            'tenant_id' => $tenantId,
            'company_id' => $companyId,
            'car_model_id' => $carModelId,
            'registration' => 'CL123',
            'color' => 'Black',
            'vin' => 'VINCL123',
            'manufacture_year' => 2020,
            'registration_year' => 2020,
            'purchase_date' => '2020-01-01',
            'purchase_price' => 10000,
            'purchase_type' => 'uk',
            'fleet_status' => Car::FLEET_STATUS_AVAILABLE_FOR_RENT,
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

        return $car->fresh(['mots', 'roadTaxes', 'phvs']);
    }
}
