<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\BankAccount;
use App\Models\Car;
use App\Models\Company;
use App\Models\DailyFinancialSheet;
use App\Models\Driver;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DailyFinancialSheetService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\TestCase;

class DailyFinancialSheetTest extends TestCase
{
    private Tenant $tenant;

    private Company $company;

    private Driver $driver;

    private User $employee;

    private User $approver;

    private BankAccount $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpDatabase();

        $this->tenant = Tenant::query()->create([
            'company_name' => 'DFS Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->company = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'DFS Company',
        ]);

        $this->driver = Driver::query()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Daily',
            'last_name' => 'Driver',
            'email' => 'daily-driver@example.com',
            'status' => 'active',
        ]);

        $this->bankAccount = BankAccount::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'bank_name' => 'Barclays',
            'account_number' => '12345678',
        ]);

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
        Schema::dropIfExists('agreements');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('daily_financial_sheets');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('cars');
        Schema::dropIfExists('car_models');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tenants');

        parent::tearDown();
    }

    public function test_payment_store_creates_pending_payment_without_allocations(): void
    {
        $invoice = $this->createInvoice(200);
        $date = now()->toDateString();

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $response = $this->post(route('payments.store'), [
            'driver_id' => $this->driver->id,
            'payment_method' => 'Cash',
            'payment_date' => $date,
            'amount' => 100,
            'auto_manage_invoices' => 1,
        ]);

        $response->assertRedirect(route('payments.driver', $this->driver->id));

        $payment = Payment::query()->first();
        $this->assertNotNull($payment);
        $this->assertSame(Payment::POSTING_STATUS_PENDING, $payment->posting_status);
        $this->assertSame($this->employee->id, $payment->created_by);
        $this->assertDatabaseCount('payment_allocations', 0);

        $invoice->refresh();
        $this->assertEquals(200, (float) $invoice->balance_amount);
    }

    public function test_sheet_detail_shows_cash_and_bank_totals(): void
    {
        $date = now()->toDateString();

        Payment::query()->create([
            'driver_id' => $this->driver->id,
            'payment_method' => 'Cash',
            'payment_date' => $date,
            'amount' => 200,
            'posting_status' => Payment::POSTING_STATUS_PENDING,
            'created_by' => $this->employee->id,
            'auto_allocate' => true,
        ]);

        Payment::query()->create([
            'driver_id' => $this->driver->id,
            'payment_method' => 'Bank Transfer',
            'bank_account_id' => $this->bankAccount->id,
            'payment_date' => $date,
            'amount' => 150,
            'posting_status' => Payment::POSTING_STATUS_PENDING,
            'created_by' => $this->employee->id,
            'auto_allocate' => true,
        ]);

        $car = $this->createCar();
        Expense::query()->create([
            'tenant_id' => $this->tenant->id,
            'car_id' => $car->id,
            'type' => 'MOT',
            'date' => $date,
            'description' => 'MOT test',
            'amount' => 50,
            'posting_status' => Expense::POSTING_STATUS_PENDING,
            'created_by' => $this->employee->id,
        ]);

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $response = $this->get(route('daily-financial-sheet.show', $date));

        $response->assertOk();
        $response->assertSee('Cash In');
        $response->assertSee('£200.00');
        $response->assertSee('£50.00');
        $response->assertSee('Barclays');
        $response->assertSee('£150.00');
    }

    public function test_sheet_detail_shows_car_registration_and_agreement_link(): void
    {
        $date = now()->toDateString();
        $car = $this->createCar();
        $agreement = Agreement::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $car->id,
            'start_date' => now()->subDay(),
            'end_date' => now()->addYear(),
            'agreed_rent' => 200,
            'rent_interval' => 'weekly',
            'deposit_amount' => 500,
        ]);

        Payment::query()->create([
            'driver_id' => $this->driver->id,
            'payment_method' => 'Cash',
            'payment_date' => $date,
            'amount' => 300,
            'posting_status' => Payment::POSTING_STATUS_PENDING,
            'created_by' => $this->employee->id,
            'auto_allocate' => false,
            'allocation_source_id' => $agreement->id,
            'allocation_invoice_types' => ['agreement', 'agreement_deposit'],
        ]);

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $response = $this->get(route('daily-financial-sheet.show', $date));

        $response->assertOk();
        $response->assertSee('DFS123');
        $response->assertSee('Agreement #'.$agreement->id);
        $response->assertSee(route('agreements.show', $agreement->id));
    }

    public function test_sheet_detail_shows_vehicle_for_pending_auto_allocate_driver_payment(): void
    {
        $date = now()->toDateString();
        $car = $this->createCar('AB12CDE');
        $agreement = Agreement::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $car->id,
            'start_date' => now()->subDay(),
            'end_date' => now()->addYear(),
            'agreed_rent' => 200,
            'rent_interval' => 'weekly',
            'deposit_amount' => 500,
        ]);

        Invoice::query()->create([
            'driver_id' => $this->driver->id,
            'source_id' => $agreement->id,
            'invoice_type' => 'agreement',
            'invoice_no' => 'Invoice #8001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'total_amount' => 260,
            'paid_amount' => 0,
            'balance_amount' => 260,
            'status' => 'pending',
        ]);

        Payment::query()->create([
            'driver_id' => $this->driver->id,
            'payment_method' => 'Cash',
            'payment_date' => $date,
            'amount' => 260,
            'posting_status' => Payment::POSTING_STATUS_PENDING,
            'created_by' => $this->employee->id,
            'auto_allocate' => true,
        ]);

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $response = $this->get(route('daily-financial-sheet.show', $date));

        $response->assertOk();
        $response->assertSee('AB12CDE');
        $response->assertSee('Agreement #'.$agreement->id);
        $response->assertSee(route('agreements.show', $agreement->id));
    }

    public function test_non_approver_cannot_approve_sheet(): void
    {
        $date = now()->toDateString();
        $this->createPendingPayment($date, 100);

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $response = $this->post(route('daily-financial-sheet.approve', $date));

        $response->assertForbidden();
        $this->assertDatabaseCount('daily_financial_sheets', 0);
    }

    public function test_approver_posts_payments_and_creates_approved_sheet(): void
    {
        $date = now()->toDateString();
        $invoice = $this->createInvoice(100);
        $this->createPendingPayment($date, 100);

        $this->actingAs($this->approver);
        $this->approver->switchTenant($this->tenant->id);

        $response = $this->post(route('daily-financial-sheet.approve', $date), [
            'approval_notes' => 'Cash and bank matched.',
        ]);

        $response->assertRedirect(route('daily-financial-sheet.show', $date));

        $payment = Payment::query()->first();
        $this->assertSame(Payment::POSTING_STATUS_POSTED, $payment->posting_status);
        $this->assertDatabaseCount('payment_allocations', 1);

        $invoice->refresh();
        $this->assertEquals(0, (float) $invoice->balance_amount);

        $sheet = DailyFinancialSheet::query()->first();
        $this->assertNotNull($sheet);
        $this->assertSame(DailyFinancialSheet::STATUS_APPROVED, $sheet->status);
        $this->assertSame('Cash and bank matched.', $sheet->approval_notes);
        $this->assertEquals(100, (float) $sheet->cash_in);
    }

    public function test_approved_sheet_appears_in_history(): void
    {
        $date = now()->toDateString();
        $this->createPendingPayment($date, 75);

        $this->actingAs($this->approver);
        $this->approver->switchTenant($this->tenant->id);
        $this->post(route('daily-financial-sheet.approve', $date));

        $response = $this->get(route('daily-financial-sheet.index'));

        $response->assertOk();
        $response->assertSee('Approved History');
        $response->assertSee('£75.00');
    }

    public function test_cannot_add_payment_for_already_approved_date(): void
    {
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

        $response = $this->from(route('payments.create'))
            ->post(route('payments.store'), [
                'driver_id' => $this->driver->id,
                'payment_method' => 'Cash',
                'payment_date' => $date,
                'amount' => 50,
                'auto_manage_invoices' => 1,
            ]);

        $response->assertRedirect(route('payments.create'));
        $response->assertSessionHasErrors('payment_date');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_posted_payment_allocates_to_invoice_on_approval(): void
    {
        $date = '2026-07-01';
        $invoice = $this->createInvoice(80);

        $payment = Payment::query()->create([
            'driver_id' => $this->driver->id,
            'payment_method' => 'Cash',
            'payment_date' => $date,
            'amount' => 80,
            'posting_status' => Payment::POSTING_STATUS_PENDING,
            'auto_allocate' => true,
            'created_by' => $this->employee->id,
        ]);

        app(DailyFinancialSheetService::class)->approveSheet(
            $this->tenant->id,
            $date,
            $this->approver->id,
            null
        );

        $payment->refresh();
        $invoice->refresh();

        $this->assertTrue($payment->isPosted());
        $this->assertEquals(1, PaymentAllocation::query()->count());
        $this->assertEquals(0, (float) $invoice->balance_amount);
    }

    private function createPendingPayment(string $date, float $amount): Payment
    {
        return Payment::query()->create([
            'driver_id' => $this->driver->id,
            'payment_method' => 'Cash',
            'payment_date' => $date,
            'amount' => $amount,
            'posting_status' => Payment::POSTING_STATUS_PENDING,
            'auto_allocate' => true,
            'created_by' => $this->employee->id,
        ]);
    }

    private function createInvoice(float $amount): Invoice
    {
        return Invoice::query()->create([
            'driver_id' => $this->driver->id,
            'invoice_type' => 'manual',
            'invoice_no' => 'Invoice #'.random_int(1000, 9999),
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'total_amount' => $amount,
            'paid_amount' => 0,
            'balance_amount' => $amount,
            'status' => 'pending',
        ]);
    }

    private function createCar(string $registration = 'DFS123'): Car
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
            $table->dateTime('start_date');
            $table->date('end_date');
            $table->decimal('agreed_rent', 10, 2)->default(0);
            $table->string('rent_interval')->default('weekly');
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('car_id');
            $table->string('type');
            $table->date('date');
            $table->text('description');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('document')->nullable();
            $table->string('posting_status', 20)->default('pending');
            $table->foreignId('created_by')->nullable();
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
            $table->text('approval_notes')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }
}
