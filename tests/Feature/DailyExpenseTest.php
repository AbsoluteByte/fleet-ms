<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\DailyFinancialSheet;
use App\Models\Expense;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DailyFinancialSheetService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\TestCase;

class DailyExpenseTest extends TestCase
{
    private Tenant $tenant;

    private User $employee;

    private User $approver;

    private BankAccount $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpDatabase();

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Daily Expense Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $company = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Daily Expense Company',
        ]);

        $this->bankAccount = BankAccount::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $company->id,
            'bank_name' => 'Barclays',
            'account_number' => '12345678',
        ]);

        $this->employee = User::factory()->create(['email' => 'daily-expense-employee@example.com']);
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
        Schema::dropIfExists('daily_financial_sheets');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('deposit_refunds');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tenants');

        parent::tearDown();
    }

    public function test_store_daily_expense_as_pending_cash(): void
    {
        $date = now()->toDateString();

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $response = $this->post(route('daily-expenses.store'), [
            'title' => 'Office supplies',
            'amount' => 25.50,
            'payment_method' => 'Cash',
            'date' => $date,
            'notes' => 'Stationery',
        ]);

        $response->assertRedirect(route('daily-expenses.index'));

        $expense = Expense::query()->first();
        $this->assertNotNull($expense);
        $this->assertTrue($expense->isDailyExpense());
        $this->assertSame(Expense::TYPE_DAILY, $expense->type);
        $this->assertSame('Office supplies', $expense->title);
        $this->assertSame('Office supplies', $expense->description);
        $this->assertSame('Cash', $expense->payment_method);
        $this->assertNull($expense->bank_account_id);
        $this->assertSame(Expense::POSTING_STATUS_PENDING, $expense->posting_status);
        $this->assertEquals(25.50, (float) $expense->amount);
    }

    public function test_bank_transfer_requires_bank_account(): void
    {
        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $response = $this->from(route('daily-expenses.create'))
            ->post(route('daily-expenses.store'), [
                'title' => 'Utilities',
                'amount' => 100,
                'payment_method' => 'Bank Transfer',
                'date' => now()->toDateString(),
            ]);

        $response->assertRedirect(route('daily-expenses.create'));
        $response->assertSessionHasErrors('bank_account_id');
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_bank_transfer_daily_expense_succeeds_with_account(): void
    {
        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $response = $this->post(route('daily-expenses.store'), [
            'title' => 'Utilities',
            'amount' => 100,
            'payment_method' => 'Bank Transfer',
            'bank_account_id' => $this->bankAccount->id,
            'date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('daily-expenses.index'));

        $expense = Expense::query()->first();
        $this->assertSame('Bank Transfer', $expense->payment_method);
        $this->assertSame($this->bankAccount->id, $expense->bank_account_id);
    }

    public function test_daily_expense_appears_on_dfs_and_approve_posts_it(): void
    {
        $date = now()->toDateString();

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);
        $this->post(route('daily-expenses.store'), [
            'title' => 'Parking fees',
            'amount' => 40,
            'payment_method' => 'Cash',
            'date' => $date,
        ]);

        $show = $this->get(route('daily-financial-sheet.show', $date));
        $show->assertOk();
        $show->assertSee('Parking fees');
        $show->assertSee('Daily expense');
        $show->assertSee('£40.00');

        $this->actingAs($this->approver);
        $this->approver->switchTenant($this->tenant->id);
        $approve = $this->post(route('daily-financial-sheet.approve', $date));
        $approve->assertRedirect(route('daily-financial-sheet.show', $date));

        $expense = Expense::query()->first();
        $this->assertSame(Expense::POSTING_STATUS_POSTED, $expense->posting_status);

        $sheet = DailyFinancialSheet::query()->first();
        $this->assertNotNull($sheet);
        $this->assertEquals(40, (float) $sheet->cash_out);
    }

    public function test_bank_daily_expense_counts_in_bank_out_on_approve(): void
    {
        $date = '2026-07-15';

        Expense::query()->create([
            'tenant_id' => $this->tenant->id,
            'car_id' => null,
            'type' => Expense::TYPE_DAILY,
            'title' => 'Software subscription',
            'date' => $date,
            'description' => 'Software subscription',
            'amount' => 75,
            'payment_method' => 'Bank Transfer',
            'bank_account_id' => $this->bankAccount->id,
            'posting_status' => Expense::POSTING_STATUS_PENDING,
            'created_by' => $this->employee->id,
        ]);

        app(DailyFinancialSheetService::class)->approveSheet(
            $this->tenant->id,
            $date,
            $this->approver->id,
            null
        );

        $sheet = DailyFinancialSheet::query()->first();
        $this->assertEquals(0, (float) $sheet->cash_out);
        $bankOut = collect($sheet->bank_out_json ?? []);
        $this->assertEquals(75, (float) $bankOut->sum('total'));
        $this->assertSame('Barclays', $bankOut->first()['bank_name']);
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

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('company_id');
            $table->string('bank_name');
            $table->string('account_number', 50);
            $table->timestamps();
        });

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->foreignId('bank_account_id')->nullable();
            $table->date('payment_date')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('posting_status', 20)->default('pending');
            $table->boolean('auto_allocate')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('deposit_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('agreement_id')->nullable();
            $table->foreignId('driver_id')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->foreignId('bank_account_id')->nullable();
            $table->date('refund_date')->nullable();
            $table->string('posting_status', 20)->default('pending');
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('car_id')->nullable();
            $table->string('type');
            $table->string('title')->nullable();
            $table->date('date');
            $table->text('description');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->foreignId('bank_account_id')->nullable();
            $table->string('document')->nullable();
            $table->text('notes')->nullable();
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
            $table->json('bank_out_json')->nullable();
            $table->text('approval_notes')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }
}
