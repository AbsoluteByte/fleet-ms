<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Car;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Status;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\SetupAgreementChangeCarDatabase;
use Tests\TestCase;

class PaymentsIndexTest extends TestCase
{
    use SetupAgreementChangeCarDatabase;

    private Tenant $tenant;

    private Company $company;

    private Status $activeAgreementStatus;

    private Status $replacementAgreementStatus;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAgreementChangeCarDatabase();
        $this->setUpHttpTestExtras();

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Payments Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $this->company = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Payments Company',
        ]);
        $this->activeAgreementStatus = Status::query()->create([
            'name' => 'Active',
            'type' => 'agreement',
        ]);
        $this->replacementAgreementStatus = Status::query()->create([
            'name' => 'Replacement Vehicle',
            'type' => 'agreement',
        ]);

        $this->user = User::factory()->create();
        $this->user->tenants()->attach($this->tenant->id, [
            'role' => 'admin',
            'is_primary' => true,
            'joined_at' => now(),
        ]);
        $roleId = DB::table('roles')->insertGetId([
            'name' => 'admin',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('model_has_roles')->insert([
            'role_id' => $roleId,
            'model_type' => User::class,
            'model_id' => $this->user->id,
        ]);
        $this->actingAs($this->user);
        $this->user->switchTenant($this->tenant->id);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('tenant_user');
        $this->tearDownAgreementChangeCarDatabase();

        parent::tearDown();
    }

    public function test_payments_index_shows_single_active_agreement_car_registration(): void
    {
        $driver = $this->createDriver('One', 'Agreement', 'one@example.com');
        $car = $this->createCar('REG111');
        $this->createAgreement($driver, $car);

        $response = $this->get(route('payments.index'));

        $response->assertOk();
        $response->assertSee('Vehicle');
        $response->assertSee('REG111');
        $response->assertDontSee('one@example.com');
        $response->assertDontSee('<th>Email</th>', false);
    }

    public function test_payments_index_shows_multiple_active_agreement_registrations_comma_separated(): void
    {
        $driver = $this->createDriver('Multi', 'Agreement', 'multi@example.com');
        $carA = $this->createCar('REGAAA');
        $carB = $this->createCar('REGBBB');
        $this->createAgreement($driver, $carA);
        $this->createAgreement($driver, $carB);

        $response = $this->get(route('payments.index'));

        $response->assertOk();
        $response->assertSee('REGAAA, REGBBB');
        $response->assertDontSee('multi@example.com');
    }

    public function test_payments_index_shows_replacement_agreement_car_registration_with_active_agreement(): void
    {
        $driver = $this->createDriver('Replace', 'Driver', 'replace@example.com');
        $activeCar = $this->createCar('REG111');
        $replacementCar = $this->createCar('REPREPL');
        $parentAgreement = $this->createAgreement($driver, $activeCar);
        $this->createReplacementAgreement($driver, $replacementCar, $parentAgreement);

        $response = $this->get(route('payments.index'));

        $response->assertOk();
        $response->assertSee('REG111, REPREPL');
    }

    public function test_payments_index_excludes_ended_replacement_agreement_registration(): void
    {
        $driver = $this->createDriver('Ended', 'Replace', 'ended-replace@example.com');
        $activeCar = $this->createCar('REG222');
        $replacementCar = $this->createCar('OLDREPL');
        $parentAgreement = $this->createAgreement($driver, $activeCar);
        Agreement::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'driver_id' => $driver->id,
            'car_id' => $replacementCar->id,
            'parent_agreement_id' => $parentAgreement->id,
            'start_date' => now()->subMonths(2),
            'end_date' => now()->subMonth()->toDateString(),
            'agreed_rent' => 0,
            'rent_interval' => 'Weekly',
            'deposit_amount' => 0,
            'collection_type' => 'weekly',
            'status_id' => $this->replacementAgreementStatus->id,
        ]);

        $response = $this->get(route('payments.index'));

        $response->assertOk();
        $response->assertSee('REG222');
        $response->assertDontSee('OLDREPL');
    }

    public function test_payments_index_shows_dash_when_driver_has_no_active_agreement(): void
    {
        $driver = $this->createDriver('No', 'Agreement', 'none@example.com');
        $car = $this->createCar('REG999');
        Agreement::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'driver_id' => $driver->id,
            'car_id' => $car->id,
            'start_date' => now()->subMonths(2),
            'end_date' => now()->subMonth()->toDateString(),
            'agreed_rent' => 200,
            'rent_interval' => 'Weekly',
            'deposit_amount' => 300,
            'collection_type' => 'weekly',
            'status_id' => $this->activeAgreementStatus->id,
        ]);

        $response = $this->get(route('payments.index'));

        $response->assertOk();
        $response->assertSee('—');
        $response->assertDontSee('REG999');
        $response->assertDontSee('none@example.com');
    }

    public function test_payments_index_includes_advanced_filter_panel_and_row_filter_metadata(): void
    {
        $driver = $this->createDriver('Filter', 'Meta', 'filter@example.com');
        $driver->update([
            'payment_remind_at' => '2026-07-20 14:30:00',
        ]);

        Payment::query()->create([
            'driver_id' => $driver->id,
            'payment_method' => 'Cash',
            'payment_date' => '2026-07-15',
            'amount' => 100,
            'posting_status' => Payment::POSTING_STATUS_POSTED,
            'auto_allocate' => false,
            'created_by' => $this->user->id,
        ]);

        Invoice::query()->create([
            'driver_id' => $driver->id,
            'invoice_type' => 'manual',
            'invoice_no' => 'INV-1001',
            'invoice_date' => '2026-07-10',
            'due_date' => '2026-07-17',
            'total_amount' => 200,
            'paid_amount' => 0,
            'balance_amount' => 200,
            'status' => 'pending',
        ]);

        $response = $this->get(route('payments.index'));

        $response->assertOk();
        $response->assertSee('id="paymentsFilterPanel"', false);
        $response->assertSee('id="paymentsFilterStatus"', false);
        $response->assertSee('id="paymentsReminderFrom"', false);
        $response->assertSee('id="paymentsLastPaymentFrom"', false);
        $response->assertSee('id="paymentsLatestInvoiceFrom"', false);
        $response->assertSee('Payment Due');
        $response->assertSee('Last Payment');
        $response->assertSee('10 Jul 2026');
        $response->assertSee('15 Jul 2026');
        $response->assertSee('data-driver-status="active"', false);
        $response->assertSee('data-remind-at="2026-07-20T14:30:00', false);
        $response->assertSee('data-last-payment-date="2026-07-15"', false);
        $response->assertSee('data-latest-invoice-date="2026-07-10"', false);
    }

    public function test_payments_index_shows_total_due_and_credit_without_n_plus_one_queries(): void
    {
        $driver = $this->createDriver('Due', 'Driver', 'due@example.com');

        Invoice::query()->create([
            'driver_id' => $driver->id,
            'invoice_type' => 'manual',
            'invoice_no' => 'INV-2001',
            'invoice_date' => '2026-07-01',
            'due_date' => '2026-07-08',
            'total_amount' => 150,
            'paid_amount' => 0,
            'balance_amount' => 150,
            'status' => 'pending',
        ]);

        Payment::query()->create([
            'driver_id' => $driver->id,
            'payment_method' => 'Cash',
            'payment_date' => '2026-07-05',
            'amount' => 200,
            'posting_status' => Payment::POSTING_STATUS_POSTED,
            'auto_allocate' => false,
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('payments.index'));

        $response->assertOk();
        $response->assertSee('£150.00');
        $response->assertSee('£200.00');
    }

    public function test_payments_index_shows_warning_total_due_when_pending_dfs_payment_exists(): void
    {
        $driver = $this->createDriver('Pending', 'Dfs', 'pending-dfs@example.com');

        Invoice::query()->create([
            'driver_id' => $driver->id,
            'invoice_type' => 'manual',
            'invoice_no' => 'INV-PENDING-1',
            'invoice_date' => '2026-07-01',
            'due_date' => '2026-07-08',
            'total_amount' => 150,
            'paid_amount' => 0,
            'balance_amount' => 150,
            'status' => 'pending',
        ]);

        Payment::query()->create([
            'driver_id' => $driver->id,
            'payment_method' => 'Cash',
            'payment_date' => '2026-07-05',
            'amount' => 75,
            'posting_status' => Payment::POSTING_STATUS_PENDING,
            'auto_allocate' => false,
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('payments.index'));

        $response->assertOk();
        $response->assertSee('text-warning', false);
        $response->assertSee('js-dfs-pending-amount', false);
        $response->assertSee('£75.00 pending daily financial sheet approval.', false);
        $response->assertSee('£150.00', false);
        $response->assertDontSee('text-danger', false);
    }

    public function test_payments_index_shows_paying_company_name_below_driver(): void
    {
        $driver = $this->createDriver('Paying', 'Company', 'paying@example.com');
        $car = $this->createCar('REGPAY');
        Agreement::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'driver_id' => $driver->id,
            'car_id' => $car->id,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth()->toDateString(),
            'agreed_rent' => 200,
            'rent_interval' => 'Weekly',
            'deposit_amount' => 300,
            'collection_type' => 'weekly',
            'status_id' => $this->activeAgreementStatus->id,
            'paying_company_name' => 'Metro Cars PLC',
        ]);

        $response = $this->get(route('payments.index'));

        $response->assertOk();
        $response->assertSee('<span class="paying-company-subtitle d-block">Pays via: Metro Cars PLC</span>', false);
    }

    public function test_payments_index_hides_paying_company_when_not_set(): void
    {
        $driver = $this->createDriver('No', 'PayingCo', 'no-paying@example.com');
        $this->createAgreement($driver, $this->createCar('REGNOPAY'));

        $response = $this->get(route('payments.index'));

        $response->assertOk();
        $response->assertDontSee('<span class="paying-company-subtitle', false);
    }

    public function test_payments_index_uses_first_active_agreement_paying_company_only(): void
    {
        $driver = $this->createDriver('First', 'Only', 'first-only@example.com');
        Agreement::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'driver_id' => $driver->id,
            'car_id' => $this->createCar('REGA')->id,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth()->toDateString(),
            'agreed_rent' => 200,
            'rent_interval' => 'Weekly',
            'deposit_amount' => 300,
            'collection_type' => 'weekly',
            'status_id' => $this->activeAgreementStatus->id,
            'paying_company_name' => 'First Company Ltd',
        ]);
        Agreement::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'driver_id' => $driver->id,
            'car_id' => $this->createCar('REGB')->id,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth()->toDateString(),
            'agreed_rent' => 200,
            'rent_interval' => 'Weekly',
            'deposit_amount' => 300,
            'collection_type' => 'weekly',
            'status_id' => $this->activeAgreementStatus->id,
            'paying_company_name' => 'Second Company Ltd',
        ]);

        $response = $this->get(route('payments.index'));

        $response->assertOk();
        $response->assertSee('Pays via: First Company Ltd');
        $response->assertDontSee('Pays via: Second Company Ltd');
    }

    private function setUpHttpTestExtras(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->default(1);
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

        Schema::table('drivers', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
            $table->dateTime('payment_remind_at')->nullable();
        });

        Schema::table('agreements', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_agreement_id')->nullable();
            $table->string('paying_company_name')->nullable();
        });
    }

    private function createDriver(string $firstName, string $lastName, string $email): Driver
    {
        return Driver::query()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'is_active' => true,
        ]);
    }

    private function createCar(string $registration): Car
    {
        $carModelId = (int) DB::table('car_models')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'name' => 'Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Car::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'car_model_id' => $carModelId,
            'registration' => $registration,
            'color' => 'Black',
            'fleet_status' => Car::FLEET_STATUS_AVAILABLE_FOR_RENT,
        ]);
    }

    private function createAgreement(Driver $driver, Car $car): Agreement
    {
        return Agreement::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'driver_id' => $driver->id,
            'car_id' => $car->id,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth()->toDateString(),
            'agreed_rent' => 200,
            'rent_interval' => 'Weekly',
            'deposit_amount' => 300,
            'collection_type' => 'weekly',
            'status_id' => $this->activeAgreementStatus->id,
        ]);
    }

    private function createReplacementAgreement(Driver $driver, Car $car, Agreement $parentAgreement): Agreement
    {
        return Agreement::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'driver_id' => $driver->id,
            'car_id' => $car->id,
            'parent_agreement_id' => $parentAgreement->id,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth()->toDateString(),
            'agreed_rent' => 0,
            'rent_interval' => 'Weekly',
            'deposit_amount' => 0,
            'collection_type' => 'weekly',
            'status_id' => $this->replacementAgreementStatus->id,
        ]);
    }
}
