<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Expense;
use App\Models\OtherPayment;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\Concerns\BuildsBatchPaymentPayload;
use Tests\TestCase;

class BatchPaymentTest extends TestCase
{
    use BuildsBatchPaymentPayload;

    private Tenant $tenant;

    private Company $company;

    private Driver $driver;

    private BankAccount $bankAccount;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpDatabase();

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Batch Payment Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->company = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Batch Payment Company',
        ]);

        $this->driver = Driver::query()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Batch',
            'last_name' => 'Driver',
            'email' => 'batch-payment@example.com',
            'status' => 'active',
        ]);

        $this->bankAccount = BankAccount::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'bank_name' => 'Barclays',
            'account_number' => '12345678',
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
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('other_payments');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tenants');

        parent::tearDown();
    }

    public function test_driver_payment_store_creates_multiple_payments(): void
    {
        $response = $this->post(route('payments.store'), array_merge([
            'driver_id' => $this->driver->id,
            'auto_manage_invoices' => 1,
        ], $this->batchPaymentsField([
            ['payment_method' => 'Cash', 'amount' => 50],
            ['payment_method' => 'Bank Transfer', 'bank_account_id' => $this->bankAccount->id, 'amount' => 75],
        ])));

        $response->assertRedirect(route('payments.driver', $this->driver->id));
        $this->assertDatabaseCount('payments', 2);

        $this->assertDatabaseHas('payments', [
            'driver_id' => $this->driver->id,
            'payment_method' => 'Cash',
            'amount' => 50,
        ]);
        $this->assertDatabaseHas('payments', [
            'driver_id' => $this->driver->id,
            'payment_method' => 'Bank Transfer',
            'bank_account_id' => $this->bankAccount->id,
            'amount' => 75,
        ]);
    }

    public function test_daily_expense_store_creates_multiple_expenses(): void
    {
        $response = $this->post(route('daily-expenses.store'), array_merge([
            'daily_expense_type' => Expense::DAILY_TYPE_OFFICE,
            'title' => 'Office supplies',
            'date' => now()->toDateString(),
        ], $this->batchPaymentsField([
            ['payment_method' => 'Cash', 'amount' => 20],
            ['payment_method' => 'Bank Transfer', 'bank_account_id' => $this->bankAccount->id, 'amount' => 30],
        ])));

        $response->assertRedirect(route('daily-expenses.index'));
        $this->assertDatabaseCount('expenses', 2);
    }

    public function test_other_payment_store_creates_multiple_records(): void
    {
        $response = $this->post(route('other-payments.store'), array_merge([
            'other_payment_type' => OtherPayment::TYPE_OFFICE,
            'title' => 'Car sale',
        ], $this->batchPaymentsField([
            ['payment_method' => 'Cash', 'amount' => 1000],
            ['payment_method' => 'Bank Transfer', 'bank_account_id' => $this->bankAccount->id, 'amount' => 2000],
        ])));

        $response->assertRedirect(route('other-payments.index'));
        $this->assertDatabaseCount('other_payments', 2);
    }

    private function setUpDatabase(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('tenant_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('user_id');
            $table->string('role')->nullable();
            $table->boolean('is_primary')->default(false);
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
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
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
            $table->foreignId('driver_id');
            $table->string('payment_no')->nullable();
            $table->string('payment_method')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->date('payment_date')->nullable();
            $table->decimal('amount', 12, 2);
            $table->text('notes')->nullable();
            $table->string('posting_status', 20)->default('pending');
            $table->boolean('auto_allocate')->default(true);
            $table->json('pending_manual_allocations')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('invoice_type')->nullable();
            $table->decimal('balance_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id');
            $table->foreignId('invoice_id');
            $table->decimal('allocated_amount', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->unsignedBigInteger('car_id')->nullable();
            $table->string('type')->nullable();
            $table->string('daily_expense_type')->nullable();
            $table->string('title')->nullable();
            $table->date('date')->nullable();
            $table->string('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->string('document')->nullable();
            $table->text('notes')->nullable();
            $table->string('posting_status', 20)->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('other_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->string('other_payment_type')->nullable();
            $table->unsignedBigInteger('car_id')->nullable();
            $table->string('title')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('document')->nullable();
            $table->text('notes')->nullable();
            $table->string('posting_status', 20)->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }
}
