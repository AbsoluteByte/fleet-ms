<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Car;
use App\Models\CarStatusHistory;
use App\Models\Company;
use App\Models\OtherPayment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\Concerns\BuildsBatchPaymentPayload;
use Tests\Concerns\SetupPhvlManagementDatabase;
use Tests\TestCase;

class CarStatusSoldBankAccountTest extends TestCase
{
    use BuildsBatchPaymentPayload;
    use SetupPhvlManagementDatabase;

    private Tenant $tenant;

    private Company $company;

    private int $carModelId;

    private User $user;

    private BankAccount $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpPhvlManagementDatabase();
        $this->setUpBankAccountsTable();
        $this->setUpOtherPaymentsTable();
        $this->setUpDriversTable();
        $this->setUpRolesTables();

        $this->tenant = Tenant::create([
            'company_name' => 'Sold Bank Test Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->company = Company::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Sold Bank Test Company',
        ]);

        $this->carModelId = (int) DB::table('car_models')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->user = User::factory()->create();
        $this->user->tenants()->attach($this->tenant->id, [
            'role' => 'admin',
            'is_primary' => true,
            'joined_at' => now(),
        ]);

        $this->bankAccount = BankAccount::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'bank_name' => 'Barclays',
            'account_number' => '12345678',
        ]);

        $this->actingAs($this->user);
        $this->user->switchTenant($this->tenant->id);
        $this->user->assignRole('admin');
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('other_payments');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('drivers');
        $this->tearDownPhvlManagementDatabase();

        parent::tearDown();
    }

    public function test_car_status_create_includes_batch_payment_rows_for_sold_form(): void
    {
        $response = $this->get(route('car-status.create'));

        $response->assertOk();
        $response->assertSee('data-batch-payment-rows', false);
        $response->assertSee('fleet-sold-payments', false);
        $response->assertSee('Barclays', false);
    }

    public function test_store_sold_with_bank_payment_creates_other_payment_with_bank_account(): void
    {
        $car = $this->createCar('SLD001', Car::FLEET_STATUS_AVAILABLE_FOR_RENT);

        $response = $this->post(route('car-status.store'), array_merge([
            'car_id' => $car->id,
            'target_status' => 'sold',
            'payload' => $this->soldPayload([
                'sell_price' => 5000,
            ]),
        ], $this->batchPaymentsField([
            [
                'payment_method' => 'Bank Transfer',
                'bank_account_id' => $this->bankAccount->id,
                'amount' => 5000,
                'payment_date' => '2026-07-01',
            ],
        ], 'sold_payments')));

        $response->assertRedirect(route('cars.show', $car));

        $car->refresh();
        $this->assertSame('sold', $car->fleet_status);

        $history = CarStatusHistory::query()->where('car_id', $car->id)->latest('id')->first();
        $this->assertNotNull($history);
        $this->assertSame(5000.0, (float) ($history->status_data['sell_price'] ?? 0));
        $this->assertSame('bank', $history->status_data['payment_terms'] ?? null);
        $this->assertSame($this->bankAccount->id, (int) ($history->status_data['bank_account_id'] ?? 0));

        $otherPayment = OtherPayment::query()->first();
        $this->assertNotNull($otherPayment);
        $this->assertSame('Bank Transfer', $otherPayment->payment_method);
        $this->assertSame($this->bankAccount->id, $otherPayment->bank_account_id);
    }

    public function test_store_sold_bank_transfer_requires_bank_account(): void
    {
        $car = $this->createCar('SLD002', Car::FLEET_STATUS_AVAILABLE_FOR_RENT);

        $response = $this->from(route('car-status.create'))
            ->post(route('car-status.store'), array_merge([
                'car_id' => $car->id,
                'target_status' => 'sold',
                'payload' => $this->soldPayload([
                    'sell_price' => 5000,
                ]),
            ], $this->batchPaymentsField([
                [
                    'payment_method' => 'Bank Transfer',
                    'amount' => 5000,
                    'payment_date' => '2026-07-01',
                ],
            ], 'sold_payments')));

        $response->assertRedirect(route('car-status.create'));
        $response->assertSessionHasErrors('sold_payments.0.bank_account_id');
        $this->assertDatabaseCount('car_status_histories', 0);
    }

    public function test_store_sold_with_cash_payment_does_not_require_bank_account(): void
    {
        $car = $this->createCar('SLD003', Car::FLEET_STATUS_AVAILABLE_FOR_RENT);

        $response = $this->post(route('car-status.store'), array_merge([
            'car_id' => $car->id,
            'target_status' => 'sold',
            'payload' => $this->soldPayload([
                'sell_price' => 5000,
            ]),
        ], $this->batchPaymentsField([
            [
                'payment_method' => 'Cash',
                'amount' => 5000,
                'payment_date' => '2026-07-01',
            ],
        ], 'sold_payments')));

        $response->assertRedirect(route('cars.show', $car));

        $history = CarStatusHistory::query()->where('car_id', $car->id)->latest('id')->first();
        $this->assertNotNull($history);
        $this->assertSame('cash', $history->status_data['payment_terms'] ?? null);
        $this->assertArrayNotHasKey('bank_account_id', $history->status_data ?? []);
    }

    public function test_store_sold_with_split_payments_creates_multiple_other_payments(): void
    {
        $car = $this->createCar('SLD004', Car::FLEET_STATUS_AVAILABLE_FOR_RENT);

        $response = $this->post(route('car-status.store'), array_merge([
            'car_id' => $car->id,
            'target_status' => 'sold',
            'payload' => $this->soldPayload([
                'sell_price' => 20000,
            ]),
        ], $this->batchPaymentsField([
            [
                'payment_method' => 'Cash',
                'amount' => 5000,
                'payment_date' => '2026-07-01',
            ],
            [
                'payment_method' => 'Bank Transfer',
                'bank_account_id' => $this->bankAccount->id,
                'amount' => 15000,
                'payment_date' => '2026-07-01',
            ],
        ], 'sold_payments')));

        $response->assertRedirect(route('cars.show', $car));
        $this->assertDatabaseCount('other_payments', 2);

        $history = CarStatusHistory::query()->where('car_id', $car->id)->latest('id')->first();
        $this->assertCount(2, $history->status_data['payments'] ?? []);
        $this->assertArrayNotHasKey('payment_terms', $history->status_data ?? []);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function soldPayload(array $overrides = []): array
    {
        return array_merge([
            'sell_date' => '2026-07-01',
            'sell_price' => 5000,
            'buyer_name' => 'John Buyer',
            'buyer_contact' => '07700900000',
            'buyer_address' => '1 Test Street',
        ], $overrides);
    }

    private function createCar(string $registration, string $fleetStatus): Car
    {
        return Car::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'car_model_id' => $this->carModelId,
            'registration' => $registration,
            'fleet_status' => $fleetStatus,
            'createdBy' => $this->user->id,
            'updatedBy' => $this->user->id,
        ]);
    }

    private function setUpBankAccountsTable(): void
    {
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
    }

    private function setUpOtherPaymentsTable(): void
    {
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
    }

    private function setUpDriversTable(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    private function setUpRolesTables(): void
    {
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

        DB::table('roles')->insert([
            'id' => 1,
            'name' => 'admin',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
