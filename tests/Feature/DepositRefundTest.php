<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\BankAccount;
use App\Models\Car;
use App\Models\Company;
use App\Models\DailyFinancialSheet;
use App\Models\DepositRefund;
use App\Models\Driver;
use App\Models\Status;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DailyFinancialSheetService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\TestCase;

class DepositRefundTest extends TestCase
{
    private Tenant $tenant;

    private Company $company;

    private Driver $driver;

    private User $employee;

    private User $approver;

    private BankAccount $bankAccount;

    private Status $activeStatus;

    private Status $terminatedStatus;

    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpDatabase();

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Refund Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->company = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Refund Company',
        ]);

        $this->driver = Driver::query()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Abdi',
            'last_name' => 'Ali',
            'email' => 'abdi@example.com',
            'status' => 'active',
        ]);

        $this->bankAccount = BankAccount::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'bank_name' => 'Barclays',
            'account_number' => '12345678',
        ]);

        $this->activeStatus = Status::query()->create([
            'name' => 'Active',
            'type' => 'agreement',
            'color' => '#00ff00',
        ]);

        $this->terminatedStatus = Status::query()->create([
            'name' => 'Terminated',
            'type' => 'agreement',
            'color' => '#ff0000',
        ]);

        $this->car = $this->createCar('AB12CDE');

        $this->employee = User::factory()->create(['email' => 'employee@example.com']);
        $this->employee->tenants()->attach($this->tenant->id, [
            'role' => 'admin',
            'is_primary' => true,
            'joined_at' => now(),
        ]);

        $this->approver = User::factory()->create(['email' => 'jawad@samoretraders.com', 'name' => 'Jawad']);
        $this->approver->tenants()->attach($this->tenant->id, [
            'role' => 'admin',
            'is_primary' => true,
            'joined_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('driver_credit_transaction_lines');
        Schema::dropIfExists('driver_credit_transactions');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('deposit_refunds');
        Schema::dropIfExists('agreement_deductions');
        Schema::dropIfExists('daily_financial_sheets');
        Schema::dropIfExists('agreements');
        Schema::dropIfExists('statuses');
        Schema::dropIfExists('cars');
        Schema::dropIfExists('car_models');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('expenses');

        parent::tearDown();
    }

    public function test_closed_agreement_can_request_pending_deposit_refund(): void
    {
        $agreement = $this->createClosedAgreement(500);
        $date = now()->toDateString();

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $response = $this->from(route('agreements.show', $agreement))
            ->post(route('agreements.refund-deposit', $agreement), [
                'amount' => 500,
                'payment_method' => 'Cash',
                'refund_date' => $date,
                'notes' => 'Full deposit return',
            ]);

        $response->assertRedirect(route('agreements.show', $agreement));

        $refund = DepositRefund::query()->first();
        $this->assertNotNull($refund);
        $this->assertSame(DepositRefund::POSTING_STATUS_PENDING, $refund->posting_status);
        $this->assertEquals(500, (float) $refund->amount);
        $this->assertSame($agreement->id, $refund->agreement_id);
        $this->assertSame($this->employee->id, $refund->created_by);
    }

    public function test_active_agreement_cannot_refund_deposit(): void
    {
        $agreement = Agreement::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $this->car->id,
            'status_id' => $this->activeStatus->id,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addYear(),
            'agreed_rent' => 200,
            'rent_interval' => 'weekly',
            'deposit_amount' => 500,
        ]);

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $response = $this->from(route('agreements.show', $agreement))
            ->post(route('agreements.refund-deposit', $agreement), [
                'amount' => 500,
                'payment_method' => 'Cash',
                'refund_date' => now()->toDateString(),
            ]);

        $response->assertRedirect(route('agreements.show', $agreement));
        $response->assertSessionHasErrors('agreement');
        $this->assertDatabaseCount('deposit_refunds', 0);
    }

    public function test_second_refund_is_rejected(): void
    {
        $agreement = $this->createClosedAgreement(400);
        $date = now()->toDateString();

        DepositRefund::query()->create([
            'tenant_id' => $this->tenant->id,
            'agreement_id' => $agreement->id,
            'driver_id' => $this->driver->id,
            'amount' => 400,
            'payment_method' => 'Cash',
            'refund_date' => $date,
            'posting_status' => DepositRefund::POSTING_STATUS_PENDING,
            'created_by' => $this->employee->id,
        ]);

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $response = $this->from(route('agreements.show', $agreement))
            ->post(route('agreements.refund-deposit', $agreement), [
                'amount' => 400,
                'payment_method' => 'Cash',
                'refund_date' => $date,
            ]);

        $response->assertRedirect(route('agreements.show', $agreement));
        $response->assertSessionHasErrors('agreement');
        $this->assertDatabaseCount('deposit_refunds', 1);
    }

    public function test_bank_transfer_requires_bank_account(): void
    {
        $agreement = $this->createClosedAgreement(300);

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $response = $this->from(route('agreements.show', $agreement))
            ->post(route('agreements.refund-deposit', $agreement), [
                'amount' => 300,
                'payment_method' => 'Bank Transfer',
                'refund_date' => now()->toDateString(),
            ]);

        $response->assertRedirect(route('agreements.show', $agreement));
        $response->assertSessionHasErrors('bank_account_id');
        $this->assertDatabaseCount('deposit_refunds', 0);
    }

    public function test_sheet_shows_deposit_refund_as_out_and_approve_posts_it(): void
    {
        $agreement = $this->createClosedAgreement(260);
        $date = now()->toDateString();

        DepositRefund::query()->create([
            'tenant_id' => $this->tenant->id,
            'agreement_id' => $agreement->id,
            'driver_id' => $this->driver->id,
            'amount' => 260,
            'payment_method' => 'Cash',
            'refund_date' => $date,
            'posting_status' => DepositRefund::POSTING_STATUS_PENDING,
            'created_by' => $this->employee->id,
        ]);

        $this->actingAs($this->approver);
        $this->approver->switchTenant($this->tenant->id);

        $show = $this->get(route('daily-financial-sheet.show', $date));
        $show->assertOk();
        $show->assertSee('Deposit refund');
        $show->assertSee('£260.00');
        $show->assertSee('OUT');

        $approve = $this->post(route('daily-financial-sheet.approve', $date), [
            'approval_notes' => 'Matched cash.',
        ]);
        $approve->assertRedirect(route('daily-financial-sheet.show', $date));

        $refund = DepositRefund::query()->first();
        $this->assertSame(DepositRefund::POSTING_STATUS_POSTED, $refund->posting_status);

        $sheet = DailyFinancialSheet::query()->first();
        $this->assertNotNull($sheet);
        $this->assertEquals(260, (float) $sheet->cash_out);
    }

    public function test_non_approver_cannot_approve_sheet_with_refund(): void
    {
        $agreement = $this->createClosedAgreement(100);
        $date = now()->toDateString();

        DepositRefund::query()->create([
            'tenant_id' => $this->tenant->id,
            'agreement_id' => $agreement->id,
            'driver_id' => $this->driver->id,
            'amount' => 100,
            'payment_method' => 'Cash',
            'refund_date' => $date,
            'posting_status' => DepositRefund::POSTING_STATUS_PENDING,
            'created_by' => $this->employee->id,
        ]);

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $response = $this->post(route('daily-financial-sheet.approve', $date));
        $response->assertForbidden();

        $this->assertSame(
            DepositRefund::POSTING_STATUS_PENDING,
            DepositRefund::query()->first()->posting_status
        );
    }

    public function test_can_refund_on_already_approved_sheet_date(): void
    {
        $agreement = $this->createClosedAgreement(150);
        $date = now()->toDateString();

        DailyFinancialSheet::query()->create([
            'tenant_id' => $this->tenant->id,
            'sheet_date' => $date,
            'status' => DailyFinancialSheet::STATUS_APPROVED,
            'cash_in' => 0,
            'cash_out' => 0,
            'approved_by' => $this->approver->id,
            'approved_at' => now(),
        ]);

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $response = $this->from(route('agreements.show', $agreement))
            ->post(route('agreements.refund-deposit', $agreement), [
                'amount' => 150,
                'payment_method' => 'Cash',
                'refund_date' => $date,
            ]);

        $response->assertRedirect(route('agreements.show', $agreement));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('deposit_refunds', 1);
        $this->assertSame(
            DepositRefund::POSTING_STATUS_PENDING,
            DepositRefund::query()->first()->posting_status
        );
    }

    public function test_bank_transfer_refund_appears_in_bank_out_totals(): void
    {
        $agreement = $this->createClosedAgreement(180);
        $date = now()->toDateString();

        DepositRefund::query()->create([
            'tenant_id' => $this->tenant->id,
            'agreement_id' => $agreement->id,
            'driver_id' => $this->driver->id,
            'amount' => 180,
            'payment_method' => 'Bank Transfer',
            'bank_account_id' => $this->bankAccount->id,
            'refund_date' => $date,
            'posting_status' => DepositRefund::POSTING_STATUS_PENDING,
            'created_by' => $this->employee->id,
        ]);

        $service = app(DailyFinancialSheetService::class);
        $entries = $service->entriesForDate($this->tenant->id, $date);
        $totals = $service->computeTotals($entries, pendingOnly: true);

        $this->assertEquals(0, $totals['cash_out']);
        $this->assertCount(1, $totals['bank_out']);
        $this->assertEquals(180, $totals['bank_out'][0]['total']);
    }

    public function test_submitted_refund_amount_cannot_reduce_server_calculated_amount(): void
    {
        $agreement = $this->createClosedAgreement(500);

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $response = $this->from(route('agreements.show', $agreement))
            ->post(route('agreements.refund-deposit', $agreement), [
                'amount' => 350,
                'payment_method' => 'Cash',
                'refund_date' => now()->toDateString(),
                'notes' => 'Car wash deducted',
            ]);

        $response->assertRedirect(route('agreements.show', $agreement));

        $refund = DepositRefund::query()->first();
        $this->assertNotNull($refund);
        $this->assertEquals(500, (float) $refund->amount);
    }

    public function test_submitted_refund_amount_above_deposit_is_ignored(): void
    {
        $agreement = $this->createClosedAgreement(200);

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $response = $this->from(route('agreements.show', $agreement))
            ->post(route('agreements.refund-deposit', $agreement), [
                'amount' => 250,
                'payment_method' => 'Cash',
                'refund_date' => now()->toDateString(),
            ]);

        $response->assertRedirect(route('agreements.show', $agreement));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('deposit_refunds', 1);
        $this->assertEquals(200, (float) DepositRefund::query()->first()->amount);
    }

    public function test_card_payment_requires_bank_account(): void
    {
        $agreement = $this->createClosedAgreement(300);

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $response = $this->from(route('agreements.show', $agreement))
            ->post(route('agreements.refund-deposit', $agreement), [
                'amount' => 300,
                'payment_method' => 'Card Payment',
                'refund_date' => now()->toDateString(),
            ]);

        $response->assertRedirect(route('agreements.show', $agreement));
        $response->assertSessionHasErrors('bank_account_id');
        $this->assertDatabaseCount('deposit_refunds', 0);
    }

    public function test_driver_credit_creates_linked_pending_refund_and_payment(): void
    {
        $agreement = $this->createClosedAgreement(400);
        $date = now()->toDateString();

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $response = $this->from(route('agreements.show', $agreement))
            ->post(route('agreements.refund-deposit', $agreement), [
                'amount' => 400,
                'payment_method' => 'Driver Credit',
                'refund_date' => $date,
                'notes' => 'Keep for next agreement',
            ]);

        $response->assertRedirect(route('agreements.show', $agreement));

        $refund = DepositRefund::query()->first();
        $this->assertNotNull($refund);
        $this->assertSame('Driver Credit', $refund->payment_method);
        $this->assertEquals(400, (float) $refund->amount);
        $this->assertSame(DepositRefund::POSTING_STATUS_PENDING, $refund->posting_status);

        $payment = \App\Models\Payment::query()->first();
        $this->assertNotNull($payment);
        $this->assertSame('Driver Credit', $payment->payment_method);
        $this->assertEquals(400, (float) $payment->amount);
        $this->assertSame(\App\Models\Payment::POSTING_STATUS_PENDING, $payment->posting_status);
        $this->assertSame($payment->id, $refund->refund_credit_payment_id);
        $this->assertEquals(400, (float) $payment->unallocated_amount);

        $service = app(DailyFinancialSheetService::class);
        $totals = $service->computeTotals($service->entriesForDate($this->tenant->id, $date), pendingOnly: false);
        $this->assertEquals(0, $totals['cash_out']);
        $this->assertEquals(0, $totals['cash_in']);
        $this->assertCount(0, $totals['bank_out']);
        $this->assertCount(0, $totals['bank_in']);

        $this->actingAs($this->approver);
        $this->approver->switchTenant($this->tenant->id);
        $this->post(route('daily-financial-sheet.approve', $date), [
            'approve_mode' => 'selected',
            'entry_ids' => ['deposit-refund-'.$refund->id],
        ])->assertSessionHasNoErrors();

        $this->assertTrue($refund->fresh()->isPosted());
        $this->assertTrue($payment->fresh()->isPosted());
        $this->assertEquals(400, (float) $payment->fresh()->unallocated_amount);
    }

    public function test_card_payment_refund_appears_in_bank_out_totals(): void
    {
        $agreement = $this->createClosedAgreement(175);
        $date = now()->toDateString();

        DepositRefund::query()->create([
            'tenant_id' => $this->tenant->id,
            'agreement_id' => $agreement->id,
            'driver_id' => $this->driver->id,
            'amount' => 175,
            'payment_method' => 'Card Payment',
            'bank_account_id' => $this->bankAccount->id,
            'refund_date' => $date,
            'posting_status' => DepositRefund::POSTING_STATUS_PENDING,
            'created_by' => $this->employee->id,
        ]);

        $service = app(DailyFinancialSheetService::class);
        $entries = $service->entriesForDate($this->tenant->id, $date);
        $totals = $service->computeTotals($entries, pendingOnly: true);

        $this->assertEquals(0, $totals['cash_out']);
        $this->assertCount(1, $totals['bank_out']);
        $this->assertEquals(175, $totals['bank_out'][0]['total']);
    }

    public function test_deductions_and_all_driver_debt_are_calculated_and_approved_atomically(): void
    {
        $agreement = $this->createClosedAgreement(500);
        $agreement->deductions()->create([
            'tenant_id' => $this->tenant->id,
            'amount' => 50,
            'notes' => 'Damage',
            'sort_order' => 0,
            'created_by' => $this->employee->id,
        ]);
        $oldestInvoiceId = $this->createInvoiceBalance(200, now()->subMonth()->toDateString());
        $newestInvoiceId = $this->createInvoiceBalance(100, now()->subWeek()->toDateString());
        $date = now()->toDateString();

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);
        $this->post(route('agreements.refund-deposit', $agreement), [
            'amount' => 1,
            'payment_method' => 'Cash',
            'refund_date' => $date,
        ])->assertSessionHasNoErrors();

        $refund = DepositRefund::query()->firstOrFail();
        $this->assertEquals(500, (float) $refund->gross_deposit_amount);
        $this->assertEquals(50, (float) $refund->deductions_amount);
        $this->assertEquals(300, (float) $refund->debt_offset_amount);
        $this->assertEquals(150, (float) $refund->amount);
        $this->assertNotNull($refund->debt_payment_id);

        $service = app(DailyFinancialSheetService::class);
        $entries = $service->entriesForDate($this->tenant->id, $date);
        $this->assertTrue($entries->contains(fn ($entry) => $entry['category'] === 'Deposit applied to driver debt'));
        $totals = $service->computeTotals($entries, pendingOnly: true);
        $this->assertEquals(150, $totals['cash_out']);
        $this->assertEquals(0, $totals['cash_in']);

        $this->actingAs($this->approver);
        $this->approver->switchTenant($this->tenant->id);
        $this->post(route('daily-financial-sheet.approve', $date), [
            'approve_mode' => 'selected',
            'entry_ids' => ['payment-'.$refund->debt_payment_id],
        ])->assertSessionHasNoErrors();

        $refund->refresh();
        $this->assertTrue($refund->isPosted());
        $this->assertTrue($refund->debtPayment->fresh()->isPosted());
        $this->assertEquals(0, (float) DB::table('invoices')->where('id', $oldestInvoiceId)->value('balance_amount'));
        $this->assertEquals(0, (float) DB::table('invoices')->where('id', $newestInvoiceId)->value('balance_amount'));
        $this->assertEquals(150, (float) DailyFinancialSheet::query()->firstOrFail()->cash_out);
    }

    public function test_zero_refund_settlement_is_still_approvable_without_payment_method(): void
    {
        $agreement = $this->createClosedAgreement(100);
        $agreement->deductions()->create([
            'tenant_id' => $this->tenant->id,
            'amount' => 20,
            'notes' => 'Cleaning',
            'sort_order' => 0,
        ]);
        $this->createInvoiceBalance(100, now()->subMonth()->toDateString());
        $date = now()->toDateString();

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);
        $this->post(route('agreements.refund-deposit', $agreement), [
            'amount' => 999,
            'refund_date' => $date,
        ])->assertSessionHasNoErrors();

        $refund = DepositRefund::query()->firstOrFail();
        $this->assertEquals(0, (float) $refund->amount);
        $this->assertEquals(80, (float) $refund->debt_offset_amount);
        $this->assertSame('No Refund Due', $refund->payment_method);

        $this->actingAs($this->approver);
        $this->approver->switchTenant($this->tenant->id);
        $this->post(route('daily-financial-sheet.approve', $date))->assertSessionHasNoErrors();

        $this->assertTrue($refund->fresh()->isPosted());
        $sheet = DailyFinancialSheet::query()->firstOrFail();
        $this->assertEquals(0, (float) $sheet->cash_in);
        $this->assertEquals(0, (float) $sheet->cash_out);
    }

    public function test_upgraded_predecessor_cannot_request_its_transferred_deposit(): void
    {
        $agreement = $this->createClosedAgreement(300);
        Agreement::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $this->car->id,
            'status_id' => $this->activeStatus->id,
            'upgraded_from_agreement_id' => $agreement->id,
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'agreed_rent' => 200,
            'rent_interval' => 'weekly',
            'deposit_amount' => 300,
        ]);

        $this->assertFalse($agreement->canRequestDepositRefund());

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);
        $this->post(route('agreements.refund-deposit', $agreement), [
            'payment_method' => 'Cash',
            'refund_date' => now()->toDateString(),
        ])->assertSessionHasErrors('agreement');
        $this->assertDatabaseCount('deposit_refunds', 0);
    }

    public function test_agreement_financial_totals_use_invoice_paid_and_balance_amounts(): void
    {
        $agreement = $this->createClosedAgreement(100);

        DB::table('invoices')->insert([
            [
                'tenant_id' => $this->tenant->id,
                'driver_id' => $this->driver->id,
                'source_id' => $agreement->id,
                'invoice_type' => 'agreement',
                'invoice_date' => now()->subMonth()->toDateString(),
                'due_date' => now()->subMonth()->toDateString(),
                'total_amount' => 300,
                'paid_amount' => 120,
                'balance_amount' => 180,
                'status' => 'partial',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => $this->tenant->id,
                'driver_id' => $this->driver->id,
                'source_id' => $agreement->id,
                'invoice_type' => 'agreement_deposit',
                'invoice_date' => now()->subMonth()->toDateString(),
                'due_date' => now()->subMonth()->toDateString(),
                'total_amount' => 100,
                'paid_amount' => 100,
                'balance_amount' => 0,
                'status' => 'paid',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->assertEquals(220, $agreement->total_paid);
        $this->assertEquals(180, $agreement->total_outstanding);
    }

    public function test_agreement_invoice_section_filters_invoices_and_links_unpaid_rows_to_add_payment(): void
    {
        $agreement = $this->createClosedAgreement(100);
        $otherAgreement = $this->createClosedAgreement(0);
        $now = now();

        DB::table('invoices')->insert([
            [
                'invoice_no' => 'INV-CURRENT-UNPAID',
                'tenant_id' => $this->tenant->id,
                'driver_id' => $this->driver->id,
                'source_id' => $agreement->id,
                'invoice_type' => 'agreement',
                'invoice_date' => $now->toDateString(),
                'due_date' => $now->toDateString(),
                'total_amount' => 200,
                'paid_amount' => 50,
                'balance_amount' => 150,
                'status' => 'partial',
                'notes' => 'Current unpaid invoice',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'invoice_no' => 'INV-CURRENT-PAID',
                'tenant_id' => $this->tenant->id,
                'driver_id' => $this->driver->id,
                'source_id' => $agreement->id,
                'invoice_type' => 'agreement_deposit',
                'invoice_date' => $now->toDateString(),
                'due_date' => $now->toDateString(),
                'total_amount' => 100,
                'paid_amount' => 100,
                'balance_amount' => 0,
                'status' => 'paid',
                'notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'invoice_no' => 'INV-OTHER-AGREEMENT',
                'tenant_id' => $this->tenant->id,
                'driver_id' => $this->driver->id,
                'source_id' => $otherAgreement->id,
                'invoice_type' => 'agreement',
                'invoice_date' => $now->toDateString(),
                'due_date' => $now->toDateString(),
                'total_amount' => 50,
                'paid_amount' => 0,
                'balance_amount' => 50,
                'status' => 'pending',
                'notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $agreement->load('invoices');
        $html = view('backend.agreements.partials.invoices', compact('agreement'))->render();

        $this->assertStringContainsString('INV-CURRENT-UNPAID', $html);
        $this->assertStringContainsString('INV-CURRENT-PAID', $html);
        $this->assertStringNotContainsString('INV-OTHER-AGREEMENT', $html);
        $this->assertStringContainsString('Partial', $html);
        $this->assertStringContainsString('Paid', $html);
        $this->assertStringContainsString(
            route('payments.create', ['driver_id' => $agreement->driver_id]),
            $html
        );
        $this->assertSame(1, substr_count($html, 'Add Payment'));
    }

    private function createInvoiceBalance(float $amount, string $invoiceDate): int
    {
        return (int) DB::table('invoices')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'driver_id' => $this->driver->id,
            'invoice_type' => 'agreement',
            'invoice_date' => $invoiceDate,
            'due_date' => $invoiceDate,
            'total_amount' => $amount,
            'paid_amount' => 0,
            'balance_amount' => $amount,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createClosedAgreement(float $deposit): Agreement
    {
        return Agreement::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $this->car->id,
            'status_id' => $this->terminatedStatus->id,
            'start_date' => now()->subMonths(2),
            'end_date' => now()->subDay(),
            'closing_date' => now()->subDay(),
            'agreed_rent' => 200,
            'rent_interval' => 'weekly',
            'deposit_amount' => $deposit,
        ]);
    }

    private function createCar(string $registration): Car
    {
        $carModelId = (int) DB::table('car_models')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Car::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'car_model_id' => $carModelId,
            'registration' => $registration,
            'status' => 'active',
        ]);
    }

    private function setUpDatabase(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
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

        Schema::create('tenant_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('user_id');
            $table->string('role')->default('admin');
            $table->boolean('is_primary')->default(true);
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('company_id');
            $table->string('bank_name');
            $table->string('account_number', 50);
            $table->timestamps();
        });

        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('color')->nullable();
            $table->timestamps();
        });

        Schema::create('car_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('company_id');
            $table->foreignId('car_model_id');
            $table->string('registration');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('driver_id')->nullable();
            $table->foreignId('car_id')->nullable();
            $table->foreignId('status_id')->nullable();
            $table->foreignId('upgraded_from_agreement_id')->nullable();
            $table->dateTime('start_date');
            $table->date('end_date');
            $table->dateTime('closing_date')->nullable();
            $table->decimal('agreed_rent', 10, 2)->default(0);
            $table->string('rent_interval')->default('weekly');
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('agreement_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('agreement_id');
            $table->decimal('amount', 12, 2);
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('deposit_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('agreement_id')->unique();
            $table->foreignId('driver_id');
            $table->decimal('amount', 12, 2);
            $table->decimal('gross_deposit_amount', 12, 2)->default(0);
            $table->decimal('deductions_amount', 12, 2)->default(0);
            $table->decimal('debt_offset_amount', 12, 2)->default(0);
            $table->foreignId('debt_payment_id')->nullable();
            $table->foreignId('refund_credit_payment_id')->nullable();
            $table->string('payment_method');
            $table->foreignId('bank_account_id')->nullable();
            $table->date('refund_date');
            $table->string('posting_status', 20)->default('pending');
            $table->foreignId('created_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('daily_financial_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->date('sheet_date');
            $table->string('status', 20)->default('open');
            $table->decimal('cash_in', 12, 2)->nullable();
            $table->decimal('cash_out', 12, 2)->nullable();
            $table->json('bank_in_json')->nullable();
            $table->json('bank_out_json')->nullable();
            $table->text('approval_notes')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_no')->nullable();
            $table->foreignId('driver_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->foreignId('bank_account_id')->nullable();
            $table->date('payment_date')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('posting_status', 20)->default('pending');
            $table->foreignId('created_by')->nullable();
            $table->boolean('auto_allocate')->default(true);
            $table->foreignId('allocation_source_id')->nullable();
            $table->json('allocation_invoice_types')->nullable();
            $table->json('pending_manual_allocations')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->nullable();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('driver_id')->nullable();
            $table->foreignId('source_id')->nullable();
            $table->string('invoice_type')->nullable();
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_amount', 12, 2)->default(0);
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id');
            $table->foreignId('invoice_id');
            $table->foreignId('driver_credit_transaction_line_id')->nullable();
            $table->decimal('allocated_amount', 12, 2)->default(0);
            $table->timestamps();
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

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('car_id')->nullable();
            $table->string('type')->nullable();
            $table->date('date')->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('posting_status', 20)->default('pending');
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });
    }
}
