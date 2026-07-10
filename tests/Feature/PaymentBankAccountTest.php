<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\TestCase;

class PaymentBankAccountTest extends TestCase
{
    private Tenant $tenant;

    private Tenant $otherTenant;

    private Company $company;

    private Driver $driver;

    private BankAccount $bankAccount;

    private BankAccount $otherBankAccount;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpPaymentDatabase();

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Payment Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->otherTenant = Tenant::query()->create([
            'company_name' => 'Other Payment Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->company = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Payment Company',
        ]);

        $otherCompany = Company::query()->create([
            'tenant_id' => $this->otherTenant->id,
            'name' => 'Other Company',
        ]);

        $this->driver = Driver::query()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Test',
            'last_name' => 'Driver',
            'email' => 'driver-payment-test@example.com',
            'status' => 'active',
        ]);

        $this->bankAccount = BankAccount::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'bank_name' => 'Barclays',
            'account_number' => '12345678',
        ]);

        $this->otherBankAccount = BankAccount::query()->create([
            'tenant_id' => $this->otherTenant->id,
            'company_id' => $otherCompany->id,
            'bank_name' => 'HSBC',
            'account_number' => '87654321',
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
        Schema::dropIfExists('daily_financial_sheets');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tenants');

        parent::tearDown();
    }

    public function test_store_saves_bank_account_for_bank_transfer_payment(): void
    {
        $response = $this->post(route('payments.store'), [
            'driver_id' => $this->driver->id,
            'payment_method' => 'Bank Transfer',
            'bank_account_id' => $this->bankAccount->id,
            'payment_date' => now()->toDateString(),
            'amount' => 100,
            'notes' => 'Test payment',
            'auto_manage_invoices' => 1,
        ]);

        $response->assertRedirect(route('payments.driver', $this->driver->id));

        $payment = Payment::query()->first();

        $this->assertNotNull($payment);
        $this->assertSame('Bank Transfer', $payment->payment_method);
        $this->assertSame($this->bankAccount->id, $payment->bank_account_id);
    }

    public function test_store_requires_bank_account_for_bank_transfer(): void
    {
        $response = $this->from(route('payments.create'))
            ->post(route('payments.store'), [
                'driver_id' => $this->driver->id,
                'payment_method' => 'Bank Transfer',
                'payment_date' => now()->toDateString(),
                'amount' => 100,
                'auto_manage_invoices' => 1,
            ]);

        $response->assertRedirect(route('payments.create'));
        $response->assertSessionHasErrors('bank_account_id');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_store_rejects_bank_account_from_another_tenant(): void
    {
        $response = $this->from(route('payments.create'))
            ->post(route('payments.store'), [
                'driver_id' => $this->driver->id,
                'payment_method' => 'Bank Transfer',
                'bank_account_id' => $this->otherBankAccount->id,
                'payment_date' => now()->toDateString(),
                'amount' => 100,
                'auto_manage_invoices' => 1,
            ]);

        $response->assertRedirect(route('payments.create'));
        $response->assertSessionHasErrors('bank_account_id');
        $this->assertDatabaseCount('payments', 0);
    }

    private function setUpPaymentDatabase(): void
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
            $table->unsignedBigInteger('createdBy')->nullable();
            $table->unsignedBigInteger('updatedBy')->nullable();
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
            $table->unsignedBigInteger('created_by')->nullable();
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
            $table->decimal('balance_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();
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

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id');
            $table->foreignId('invoice_id');
            $table->decimal('allocated_amount', 12, 2)->default(0);
            $table->timestamps();
        });
    }
}
