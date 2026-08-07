<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\TestCase;

class BankAccountTest extends TestCase
{
    private Tenant $tenant;

    private Tenant $otherTenant;

    private Company $company;

    private Company $otherCompany;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpBankAccountDatabase();

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Bank Account Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->otherTenant = Tenant::query()->create([
            'company_name' => 'Other Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->company = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Main Company',
        ]);

        $this->otherCompany = Company::query()->create([
            'tenant_id' => $this->otherTenant->id,
            'name' => 'Other Company',
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
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tenants');

        parent::tearDown();
    }

    public function test_admin_can_create_bank_account_for_own_company(): void
    {
        $response = $this->post(route('bank-accounts.store'), [
            'company_id' => $this->company->id,
            'bank_name' => 'Barclays',
            'short_name' => 'BCL',
            'account_number' => '12345678',
        ]);

        $response->assertRedirect(route('bank-accounts.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bank_accounts', [
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'bank_name' => 'Barclays',
            'short_name' => 'BCL',
            'account_number' => '12345678',
        ]);
    }

    public function test_payment_display_name_uses_short_name_when_set(): void
    {
        $bankAccount = BankAccount::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'bank_name' => 'Barclays Business Account',
            'short_name' => 'BCL',
            'account_number' => '12345678',
        ]);

        $this->assertSame('BCL', $bankAccount->paymentDisplayName());
    }

    public function test_payment_display_name_falls_back_to_bank_name(): void
    {
        $bankAccount = BankAccount::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'bank_name' => 'Barclays',
            'account_number' => '12345678',
        ]);

        $this->assertSame('Barclays', $bankAccount->paymentDisplayName());
    }

    public function test_store_rejects_company_from_another_tenant(): void
    {
        $response = $this->from(route('bank-accounts.create'))
            ->post(route('bank-accounts.store'), [
                'company_id' => $this->otherCompany->id,
                'bank_name' => 'HSBC',
                'account_number' => '87654321',
            ]);

        $response->assertRedirect(route('bank-accounts.create'));
        $response->assertSessionHasErrors('company_id');

        $this->assertDatabaseMissing('bank_accounts', [
            'account_number' => '87654321',
        ]);
    }

    public function test_index_query_only_includes_current_tenant_bank_accounts(): void
    {
        BankAccount::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'bank_name' => 'Visible Bank',
            'account_number' => '11111111',
        ]);

        BankAccount::query()->create([
            'tenant_id' => $this->otherTenant->id,
            'company_id' => $this->otherCompany->id,
            'bank_name' => 'Hidden Bank',
            'account_number' => '22222222',
        ]);

        $bankAccounts = BankAccount::query()
            ->where('tenant_id', $this->tenant->id)
            ->with('company')
            ->orderBy('bank_name')
            ->get();

        $this->assertCount(1, $bankAccounts);
        $this->assertSame('Visible Bank', $bankAccounts->first()->bank_name);
        $this->assertSame('11111111', $bankAccounts->first()->account_number);
        $this->assertSame('Main Company', $bankAccounts->first()->company?->name);
    }

    private function setUpBankAccountDatabase(): void
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

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('company_id');
            $table->string('bank_name');
            $table->string('short_name')->nullable();
            $table->string('account_number', 50);
            $table->unsignedBigInteger('createdBy')->nullable();
            $table->unsignedBigInteger('updatedBy')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'account_number']);
        });
    }
}
