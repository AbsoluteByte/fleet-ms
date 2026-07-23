<?php

namespace Tests\Feature;

use App\Models\DailyFinancialSheet;
use App\Models\Driver;
use App\Models\FinancialSheetAdjustment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\TestCase;

class PaymentManageTest extends TestCase
{
    private Tenant $tenant;

    private Driver $driver;

    private User $employee;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpDatabase();

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Payment Manage Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->driver = Driver::query()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Manage',
            'last_name' => 'Driver',
            'email' => 'manage-driver@example.com',
            'status' => 'active',
        ]);

        $this->employee = User::factory()->create(['email' => 'employee@example.com']);
        $this->employee->tenants()->attach($this->tenant->id, [
            'role' => 'admin',
            'is_primary' => true,
            'joined_at' => now(),
        ]);

        $this->manager = User::factory()->create(['email' => 'jawad@samoretraders.com', 'name' => 'Jawad']);
        $this->manager->tenants()->attach($this->tenant->id, [
            'role' => 'admin',
            'is_primary' => true,
            'joined_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('financial_sheet_adjustments');
        Schema::dropIfExists('daily_financial_sheets');
        Schema::dropIfExists('deposit_refunds');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('driver_credit_transactions');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_non_manager_cannot_edit_update_or_delete_payment(): void
    {
        $payment = $this->createPendingPayment(100);

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $this->get(route('payments.edit', $payment))->assertForbidden();
        $this->put(route('payments.update', $payment), [
            'driver_id' => $this->driver->id,
            'payment_method' => 'Cash',
            'payment_date' => now()->toDateString(),
            'amount' => 50,
        ])->assertForbidden();
        $this->delete(route('payments.destroy', $payment))->assertForbidden();
    }

    public function test_manager_can_edit_pending_payment_and_sheet_reflects_change(): void
    {
        $date = now()->toDateString();
        $payment = $this->createPendingPayment(100, $date);

        $this->actingAs($this->manager);
        $this->manager->switchTenant($this->tenant->id);

        $response = $this->put(route('payments.update', $payment), [
            'driver_id' => $this->driver->id,
            'payment_method' => 'Cash',
            'payment_date' => $date,
            'amount' => 150,
            'notes' => 'Updated pending payment',
            'auto_manage_invoices' => 1,
        ]);

        $response->assertRedirect(route('payments.show', $payment));

        $payment->refresh();
        $this->assertEquals(150, (float) $payment->amount);
        $this->assertSame('Updated pending payment', $payment->notes);

        $entries = app(\App\Services\DailyFinancialSheetService::class)
            ->entriesForDate($this->tenant->id, $date);

        $this->assertTrue($entries->contains(fn ($entry) => $entry['id'] === 'payment-'.$payment->id && (float) $entry['amount'] === 150.0));
    }

    public function test_manager_can_delete_pending_payment(): void
    {
        $date = now()->toDateString();
        $payment = $this->createPendingPayment(100, $date);

        $this->actingAs($this->manager);
        $this->manager->switchTenant($this->tenant->id);

        $response = $this->delete(route('payments.destroy', $payment));

        $response->assertRedirect(route('payments.driver', $this->driver->id));
        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);

        $entries = app(\App\Services\DailyFinancialSheetService::class)
            ->entriesForDate($this->tenant->id, $date);

        $this->assertFalse($entries->contains(fn ($entry) => $entry['id'] === 'payment-'.$payment->id));
    }

    public function test_manager_can_edit_posted_payment_and_invoice_credit_update_with_sheet_adjustment(): void
    {
        $date = now()->toDateString();
        $invoice = $this->createInvoice(100);
        $payment = $this->createPendingPayment(100, $date);
        $this->approveSheet($date);

        $payment->refresh();
        $invoice->refresh();
        $this->assertEquals(0, (float) $invoice->balance_amount);

        $this->actingAs($this->manager);
        $this->manager->switchTenant($this->tenant->id);

        $response = $this->put(route('payments.update', $payment), [
            'payment_method' => 'Cash',
            'payment_date' => $date,
            'amount' => 80,
            'notes' => 'Corrected amount',
        ]);

        $response->assertRedirect(route('payments.show', $payment));

        $payment->refresh();
        $this->assertEquals(80, (float) $payment->amount);

        $invoice->refresh();
        $this->assertEquals(20, (float) $invoice->balance_amount);

        $this->assertDatabaseHas('financial_sheet_adjustments', [
            'tenant_id' => $this->tenant->id,
            'source_id' => $payment->id,
            'event_type' => FinancialSheetAdjustment::EVENT_CORRECTION,
        ]);

        $sheet = DailyFinancialSheet::query()->first();
        $this->assertEquals(80, (float) $sheet->cash_in);

        $entries = app(\App\Services\DailyFinancialSheetService::class)
            ->entriesForDate($this->tenant->id, $date);

        $this->assertTrue($entries->contains(fn ($entry) => ($entry['posting_status'] ?? '') === 'adjustment'));
    }

    public function test_manager_can_delete_posted_payment_and_sheet_shows_reversal(): void
    {
        $date = now()->toDateString();
        $invoice = $this->createInvoice(100);
        $payment = $this->createPendingPayment(100, $date);
        $this->approveSheet($date);

        $payment->refresh();
        $invoice->refresh();
        $this->assertEquals(0, (float) $invoice->balance_amount);

        $this->actingAs($this->manager);
        $this->manager->switchTenant($this->tenant->id);

        $response = $this->delete(route('payments.destroy', $payment));

        $response->assertRedirect(route('payments.driver', $this->driver->id));
        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);

        $invoice->refresh();
        $this->assertEquals(100, (float) $invoice->balance_amount);

        $this->assertDatabaseHas('financial_sheet_adjustments', [
            'tenant_id' => $this->tenant->id,
            'source_id' => $payment->id,
            'event_type' => FinancialSheetAdjustment::EVENT_REVERSAL,
            'direction' => 'out',
            'amount' => 100,
        ]);

        $sheet = DailyFinancialSheet::query()->first();
        $this->assertEquals(0, (float) $sheet->cash_in);

        $entries = app(\App\Services\DailyFinancialSheetService::class)
            ->entriesForDate($this->tenant->id, $date);

        $this->assertTrue($entries->contains(fn ($entry) => ($entry['adjustment_event_type'] ?? '') === FinancialSheetAdjustment::EVENT_REVERSAL));
    }

    public function test_payment_detail_shows_edit_delete_only_for_manager(): void
    {
        $payment = $this->createPendingPayment(100);

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $employeeResponse = $this->get(route('payments.show', $payment));
        $employeeResponse->assertOk();
        $employeeResponse->assertDontSee('Edit Payment', false);
        $employeeResponse->assertDontSee('Delete Payment', false);

        $this->actingAs($this->manager);
        $this->manager->switchTenant($this->tenant->id);

        $managerResponse = $this->get(route('payments.show', $payment));
        $managerResponse->assertOk();
        $managerResponse->assertSee('Edit Payment', false);
        $managerResponse->assertSee('Delete Payment', false);
    }

    private function createPendingPayment(float $amount, ?string $date = null): Payment
    {
        return Payment::query()->create([
            'driver_id' => $this->driver->id,
            'payment_method' => 'Cash',
            'payment_date' => $date ?? now()->toDateString(),
            'amount' => $amount,
            'posting_status' => Payment::POSTING_STATUS_PENDING,
            'created_by' => $this->employee->id,
            'auto_allocate' => true,
        ]);
    }

    private function createInvoice(float $amount): Invoice
    {
        return Invoice::query()->create([
            'driver_id' => $this->driver->id,
            'invoice_type' => 'manual',
            'invoice_no' => 'INV-'.random_int(1000, 9999),
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'total_amount' => $amount,
            'paid_amount' => 0,
            'balance_amount' => $amount,
            'status' => 'pending',
        ]);
    }

    private function approveSheet(string $date): void
    {
        $this->actingAs($this->manager);
        $this->manager->switchTenant($this->tenant->id);

        app(\App\Services\DailyFinancialSheetService::class)->approveSheet(
            $this->tenant->id,
            $date,
            $this->manager->id,
            'Approved for test'
        );
    }

    private function setUpDatabase(): void
    {
        Schema::dropIfExists('financial_sheet_adjustments');
        Schema::dropIfExists('daily_financial_sheets');
        Schema::dropIfExists('deposit_refunds');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('driver_credit_transactions');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
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

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('status')->default('active');
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
            $table->unsignedBigInteger('allocation_source_id')->nullable();
            $table->json('allocation_invoice_types')->nullable();
            $table->json('pending_manual_allocations')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('invoice_type')->nullable();
            $table->string('invoice_no')->nullable();
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_amount', 12, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id');
            $table->foreignId('invoice_id');
            $table->decimal('allocated_amount', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('daily_financial_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->date('sheet_date');
            $table->string('status')->default('approved');
            $table->decimal('cash_in', 12, 2)->default(0);
            $table->decimal('cash_out', 12, 2)->default(0);
            $table->json('bank_in_json')->nullable();
            $table->json('bank_out_json')->nullable();
            $table->text('approval_notes')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('financial_sheet_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->date('sheet_date');
            $table->string('source_type')->default('payment');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('event_type');
            $table->string('direction');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->text('description');
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('deposit_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('agreement_id')->nullable();
            $table->foreignId('driver_id');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->nullable();
            $table->foreignId('bank_account_id')->nullable();
            $table->date('refund_date');
            $table->string('posting_status', 20)->default('pending');
            $table->foreignId('created_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('car_id')->nullable();
            $table->string('type')->nullable();
            $table->date('date');
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->foreignId('bank_account_id')->nullable();
            $table->string('posting_status', 20)->default('pending');
            $table->foreignId('created_by')->nullable();
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
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
}
