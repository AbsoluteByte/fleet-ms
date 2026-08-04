<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Car;
use App\Models\Company;
use App\Models\DailyFinancialSheet;
use App\Models\OtherPayment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DailyFinancialSheetService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\Concerns\BuildsBatchPaymentPayload;
use Tests\TestCase;

class OtherPaymentTest extends TestCase
{
    use BuildsBatchPaymentPayload;
    private Tenant $tenant;

    private User $employee;

    private User $approver;

    private BankAccount $bankAccount;

    private Company $company;

    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpDatabase();

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Other Payment Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->company = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Other Payment Company',
        ]);

        $this->bankAccount = BankAccount::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'bank_name' => 'Barclays',
            'account_number' => '12345678',
        ]);
        $carModelId = DB::table('car_models')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'name' => 'Other Payment Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->car = Car::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'car_model_id' => $carModelId,
            'registration' => 'OTHER-01',
            'fleet_status' => Car::FLEET_STATUS_AVAILABLE_FOR_RENT,
        ]);

        $this->employee = User::factory()->create(['email' => 'other-payment-employee@example.com']);
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
        Schema::dropIfExists('financial_sheet_adjustments');
        Schema::dropIfExists('car_reservation_payments');
        Schema::dropIfExists('car_reservations');
        Schema::dropIfExists('other_payments');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('driver_credit_transactions');
        Schema::dropIfExists('deposit_refunds');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('cars');
        Schema::dropIfExists('car_models');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tenants');

        parent::tearDown();
    }

    public function test_store_other_payment_as_pending_cash(): void
    {
        $date = now()->toDateString();

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $response = $this->post(route('other-payments.store'), array_merge([
            'other_payment_type' => OtherPayment::TYPE_OFFICE,
            'title' => 'Car sale — ABC123',
            'notes' => 'Sold vehicle receipt',
        ], $this->batchPaymentsField([
            ['payment_method' => 'Cash', 'amount' => 5000, 'payment_date' => $date],
        ])));

        $response->assertRedirect(route('other-payments.index'));

        $otherPayment = OtherPayment::query()->first();
        $this->assertNotNull($otherPayment);
        $this->assertSame(OtherPayment::TYPE_OFFICE, $otherPayment->other_payment_type);
        $this->assertNull($otherPayment->car_id);
        $this->assertSame('Car sale — ABC123', $otherPayment->title);
        $this->assertSame('Cash', $otherPayment->payment_method);
        $this->assertNull($otherPayment->bank_account_id);
        $this->assertSame(OtherPayment::POSTING_STATUS_PENDING, $otherPayment->posting_status);
        $this->assertEquals(5000, (float) $otherPayment->amount);
    }

    public function test_bank_transfer_requires_bank_account(): void
    {
        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $response = $this->from(route('other-payments.create'))
            ->post(route('other-payments.store'), array_merge([
                'other_payment_type' => OtherPayment::TYPE_OFFICE,
                'title' => 'Insurance payout',
            ], $this->batchPaymentsField([
                ['payment_method' => 'Bank Transfer', 'amount' => 1000],
            ])));

        $response->assertRedirect(route('other-payments.create'));
        $response->assertSessionHasErrors('payments.0.bank_account_id');
        $this->assertDatabaseCount('other_payments', 0);
    }

    public function test_bank_transfer_other_payment_succeeds_with_account(): void
    {
        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $response = $this->post(route('other-payments.store'), array_merge([
            'other_payment_type' => OtherPayment::TYPE_OFFICE,
            'title' => 'Insurance payout',
        ], $this->batchPaymentsField([
            [
                'payment_method' => 'Bank Transfer',
                'bank_account_id' => $this->bankAccount->id,
                'amount' => 1000,
            ],
        ])));

        $response->assertRedirect(route('other-payments.index'));

        $otherPayment = OtherPayment::query()->first();
        $this->assertSame('Bank Transfer', $otherPayment->payment_method);
        $this->assertSame($this->bankAccount->id, $otherPayment->bank_account_id);
    }

    public function test_bank_transfer_rejects_bank_account_from_another_tenant(): void
    {
        $otherTenant = Tenant::query()->create([
            'company_name' => 'Other Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $otherCompany = Company::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Company',
        ]);
        $otherBankAccount = BankAccount::query()->create([
            'tenant_id' => $otherTenant->id,
            'company_id' => $otherCompany->id,
            'bank_name' => 'Other Bank',
            'account_number' => '99999999',
        ]);

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $response = $this->from(route('other-payments.create'))
            ->post(route('other-payments.store'), array_merge([
                'other_payment_type' => OtherPayment::TYPE_OFFICE,
                'title' => 'Invalid bank account',
            ], $this->batchPaymentsField([
                [
                    'payment_method' => 'Bank Transfer',
                    'bank_account_id' => $otherBankAccount->id,
                    'amount' => 100,
                ],
            ])));

        $response->assertRedirect(route('other-payments.create'));
        $response->assertSessionHasErrors('payments.0.bank_account_id');
        $this->assertDatabaseCount('other_payments', 0);
    }

    public function test_index_and_create_pages_load(): void
    {
        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $this->get(route('other-payments.index'))->assertOk();
        $this->get(route('other-payments.create'))
            ->assertOk()
            ->assertSee('data-batch-payment-rows', false)
            ->assertSee('id="other-payment-car-field"', false);
    }

    public function test_vehicle_other_payment_requires_car(): void
    {
        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $response = $this->from(route('other-payments.create'))
            ->post(route('other-payments.store'), array_merge([
                'other_payment_type' => OtherPayment::TYPE_VEHICLE,
                'title' => 'Vehicle sale',
            ], $this->batchPaymentsField([
                ['payment_method' => 'Cash', 'amount' => 3000],
            ])));

        $response->assertRedirect(route('other-payments.create'));
        $response->assertSessionHasErrors('car_id');
        $this->assertDatabaseCount('other_payments', 0);
    }

    public function test_vehicle_other_payment_appears_on_dfs_with_car(): void
    {
        $date = now()->toDateString();
        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $response = $this->post(route('other-payments.store'), array_merge([
            'other_payment_type' => OtherPayment::TYPE_VEHICLE,
            'car_id' => $this->car->id,
            'title' => 'Vehicle sale',
        ], $this->batchPaymentsField([
            ['payment_method' => 'Cash', 'amount' => 3000, 'payment_date' => $date],
        ])));

        $response->assertRedirect(route('other-payments.index'));
        $otherPayment = OtherPayment::query()->firstOrFail();
        $this->assertSame(OtherPayment::TYPE_VEHICLE, $otherPayment->other_payment_type);
        $this->assertSame($this->car->id, $otherPayment->car_id);

        $index = $this->get(route('other-payments.index'));
        $index->assertOk();
        $index->assertSee('Vehicle sale');
        $index->assertSee('OTHER-01');

        $show = $this->get(route('daily-financial-sheet.show', $date));
        $show->assertOk();
        $show->assertSee('Other payment — Vehicle');
        $show->assertSee('OTHER-01');
    }

    public function test_vehicle_other_payment_rejects_car_from_another_tenant(): void
    {
        $otherTenant = Tenant::query()->create([
            'company_name' => 'Other Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $otherCompany = Company::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Company',
        ]);
        $otherModelId = DB::table('car_models')->insertGetId([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $otherCar = Car::query()->create([
            'tenant_id' => $otherTenant->id,
            'company_id' => $otherCompany->id,
            'car_model_id' => $otherModelId,
            'registration' => 'OTHER-TENANT',
            'fleet_status' => Car::FLEET_STATUS_AVAILABLE_FOR_RENT,
        ]);

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);
        $response = $this->from(route('other-payments.create'))
            ->post(route('other-payments.store'), array_merge([
                'other_payment_type' => OtherPayment::TYPE_VEHICLE,
                'car_id' => $otherCar->id,
                'title' => 'Invalid vehicle payment',
            ], $this->batchPaymentsField([
                ['payment_method' => 'Cash', 'amount' => 100],
            ])));

        $response->assertRedirect(route('other-payments.create'));
        $response->assertSessionHasErrors('car_id');
        $this->assertDatabaseCount('other_payments', 0);
    }

    public function test_other_payment_appears_on_dfs_and_approve_posts_it(): void
    {
        $date = now()->toDateString();

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);
        $this->post(route('other-payments.store'), array_merge([
            'other_payment_type' => OtherPayment::TYPE_OFFICE,
            'title' => 'Misc receipt',
        ], $this->batchPaymentsField([
            ['payment_method' => 'Cash', 'amount' => 250, 'payment_date' => $date],
        ])));

        $show = $this->get(route('daily-financial-sheet.show', $date));
        $show->assertOk();
        $show->assertSee('Misc receipt');
        $show->assertSee('Other payment — Office');
        $show->assertSee('£250.00');

        $this->actingAs($this->approver);
        $this->approver->switchTenant($this->tenant->id);
        $approve = $this->post(route('daily-financial-sheet.approve', $date));
        $approve->assertRedirect(route('daily-financial-sheet.show', $date));

        $otherPayment = OtherPayment::query()->first();
        $this->assertSame(OtherPayment::POSTING_STATUS_POSTED, $otherPayment->posting_status);

        $sheet = DailyFinancialSheet::query()->first();
        $this->assertNotNull($sheet);
        $this->assertEquals(250, (float) $sheet->cash_in);
    }

    public function test_bank_other_payment_counts_in_bank_in_on_approve(): void
    {
        $date = '2026-07-15';

        OtherPayment::query()->create([
            'tenant_id' => $this->tenant->id,
            'other_payment_type' => OtherPayment::TYPE_OFFICE,
            'title' => 'Insurance settlement',
            'amount' => 1500,
            'payment_method' => 'Bank Transfer',
            'bank_account_id' => $this->bankAccount->id,
            'payment_date' => $date,
            'posting_status' => OtherPayment::POSTING_STATUS_PENDING,
            'created_by' => $this->employee->id,
        ]);

        app(DailyFinancialSheetService::class)->approveSheet(
            $this->tenant->id,
            $date,
            $this->approver->id,
            null
        );

        $sheet = DailyFinancialSheet::query()->first();
        $this->assertEquals(0, (float) $sheet->cash_in);
        $bankIn = collect($sheet->bank_in_json ?? []);
        $this->assertEquals(1500, (float) $bankIn->sum('total'));
        $this->assertSame('Barclays', $bankIn->first()['bank_name']);
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

        Schema::create('car_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('company_id');
            $table->foreignId('car_model_id');
            $table->string('registration')->nullable();
            $table->string('fleet_status')->default('available_for_rent');
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

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('car_id')->nullable();
            $table->string('type');
            $table->string('daily_expense_type', 20)->nullable();
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

        Schema::create('other_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->string('other_payment_type', 20)->default('office');
            $table->foreignId('car_id')->nullable();
            $table->string('title');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method');
            $table->foreignId('bank_account_id')->nullable();
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->string('document')->nullable();
            $table->string('posting_status', 20)->default('pending');
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('car_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('car_id')->nullable();
            $table->date('reservation_date')->nullable();
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->string('posting_status', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('car_reservation_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_reservation_id');
            $table->string('payment_method')->nullable();
            $table->foreignId('bank_account_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('posting_status', 20)->default('pending');
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
    }
}
