<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Car;
use App\Models\CarStatusHistory;
use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\Concerns\SetupPhvlManagementDatabase;
use Tests\TestCase;

class CarStatusSoldBankAccountTest extends TestCase
{
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

        $this->carModelId = (int) \Illuminate\Support\Facades\DB::table('car_models')->insertGetId([
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
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('drivers');
        $this->tearDownPhvlManagementDatabase();

        parent::tearDown();
    }

    public function test_car_status_create_includes_bank_account_select_for_sold_form(): void
    {
        $response = $this->get(route('car-status.create'));

        $response->assertOk();
        $response->assertSee('id="fleet_sold_bank_account_id"', false);
        $response->assertSee('value="'.$this->bankAccount->id.'"', false);
        $response->assertSee('Barclays', false);
        $response->assertSee('data-bank-account-field', false);
    }

    public function test_store_sold_with_bank_payment_terms_saves_bank_account_id(): void
    {
        $car = $this->createCar('SLD001', Car::FLEET_STATUS_AVAILABLE_FOR_RENT);

        $response = $this->post(route('car-status.store'), [
            'car_id' => $car->id,
            'target_status' => 'sold',
            'payload' => $this->soldPayload([
                'payment_terms' => 'bank',
                'bank_account_id' => $this->bankAccount->id,
            ]),
        ]);

        $response->assertRedirect(route('cars.show', $car));

        $car->refresh();
        $this->assertSame('sold', $car->fleet_status);

        $history = CarStatusHistory::query()->where('car_id', $car->id)->latest('id')->first();
        $this->assertNotNull($history);
        $this->assertSame('bank', $history->status_data['payment_terms'] ?? null);
        $this->assertSame($this->bankAccount->id, (int) ($history->status_data['bank_account_id'] ?? 0));
    }

    public function test_store_sold_with_bank_payment_terms_requires_bank_account(): void
    {
        $car = $this->createCar('SLD002', Car::FLEET_STATUS_AVAILABLE_FOR_RENT);

        $response = $this->from(route('car-status.create'))
            ->post(route('car-status.store'), [
                'car_id' => $car->id,
                'target_status' => 'sold',
                'payload' => $this->soldPayload([
                    'payment_terms' => 'bank',
                ]),
            ]);

        $response->assertRedirect(route('car-status.create'));
        $response->assertSessionHasErrors('payload.bank_account_id');
        $this->assertDatabaseCount('car_status_histories', 0);
    }

    public function test_store_sold_with_cash_payment_terms_does_not_require_bank_account(): void
    {
        $car = $this->createCar('SLD003', Car::FLEET_STATUS_AVAILABLE_FOR_RENT);

        $response = $this->post(route('car-status.store'), [
            'car_id' => $car->id,
            'target_status' => 'sold',
            'payload' => $this->soldPayload([
                'payment_terms' => 'cash',
            ]),
        ]);

        $response->assertRedirect(route('cars.show', $car));

        $history = CarStatusHistory::query()->where('car_id', $car->id)->latest('id')->first();
        $this->assertNotNull($history);
        $this->assertSame('cash', $history->status_data['payment_terms'] ?? null);
        $this->assertArrayNotHasKey('bank_account_id', $history->status_data ?? []);
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
            'payment_terms' => 'cash',
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
