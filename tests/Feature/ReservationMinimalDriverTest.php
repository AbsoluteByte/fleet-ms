<?php

namespace Tests\Feature;

use App\Models\CarReservation;
use App\Models\Driver;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\TestCase;

class ReservationMinimalDriverTest extends TestCase
{
    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpReservationDatabase();

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Reservation Tenant',
            'status' => Tenant::STATUS_ACTIVE,
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
        Schema::dropIfExists('car_reservations');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('countries');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tenants');

        parent::tearDown();
    }

    public function test_store_creates_reservation_with_minimal_new_driver(): void
    {
        $response = $this->post(route('reservations.store'), [
            'driver_mode' => 'new',
            'first_name' => 'Ali',
            'reservation_date' => '2026-06-25',
            'pick_up_date' => '2026-06-30',
            'agreed_rent' => 100,
            'agreed_advance' => 50,
            'amount_paid' => 25,
            'payment_method' => 'Cash',
        ]);

        $response->assertRedirect(route('reservations.index'));
        $response->assertSessionHasNoErrors();

        $driver = Driver::query()->first();
        $this->assertNotNull($driver);
        $this->assertSame('Ali', $driver->first_name);
        $this->assertNull($driver->last_name);
        $this->assertNull($driver->email);

        $reservation = CarReservation::query()->first();
        $this->assertNotNull($reservation);
        $this->assertSame($driver->id, $reservation->driver_id);
        $this->assertSame('Ali', $reservation->customer_name);
    }

    public function test_update_rejects_minimal_new_driver(): void
    {
        $reservation = CarReservation::query()->create([
            'tenant_id' => $this->tenant->id,
            'customer_name' => 'Existing Customer',
            'reservation_date' => '2026-06-25',
            'pick_up_date' => '2026-06-30',
            'agreed_rent' => 100,
            'agreed_advance' => 50,
            'amount_paid' => 25,
            'balance_payable_on_pickup' => 125,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $response = $this->from(route('reservations.edit', $reservation))
            ->put(route('reservations.update', $reservation), [
                'driver_mode' => 'new',
                'first_name' => 'Sara',
                'reservation_date' => '2026-06-25',
                'pick_up_date' => '2026-06-30',
                'agreed_rent' => 100,
                'agreed_advance' => 50,
                'amount_paid' => 25,
                'payment_method' => 'Cash',
            ]);

        $response->assertRedirect(route('reservations.edit', $reservation));
        $response->assertSessionHasErrors(['last_name', 'email']);
        $this->assertDatabaseCount('drivers', 0);
    }

    public function test_reservation_update_completes_linked_driver(): void
    {
        $driver = Driver::query()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Ali',
        ]);

        $reservation = CarReservation::query()->create([
            'tenant_id' => $this->tenant->id,
            'driver_id' => $driver->id,
            'customer_name' => 'Ali',
            'reservation_date' => '2026-06-25',
            'pick_up_date' => '2026-06-30',
            'agreed_rent' => 100,
            'agreed_advance' => 50,
            'amount_paid' => 25,
            'balance_payable_on_pickup' => 125,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $response = $this->from(route('reservations.edit', $reservation))
            ->put(route('reservations.update', $reservation), [
                'driver_mode' => 'existing',
                'driver_id' => $driver->id,
                'first_name' => 'Ali',
                'last_name' => 'Khan',
                'dob' => '1990-05-15',
                'email' => 'ali.khan@example.com',
                'phone_number' => '07000000007',
                'address1' => '10 Test Road',
                'post_code' => 'E1 1AA',
                'town' => 'London',
                'country_id' => 1,
                'driver_license_number' => 'LIC000777',
                'driver_license_expiry_date' => '2027-12-31',
                'next_of_kin' => 'Sara Khan',
                'next_of_kin_phone' => '07000000008',
                'reservation_date' => '2026-06-25',
                'pick_up_date' => '2026-06-30',
                'agreed_rent' => 100,
                'agreed_advance' => 50,
                'amount_paid' => 25,
                'payment_method' => 'Cash',
            ]);

        $response->assertRedirect(route('reservations.index'));
        $response->assertSessionHasNoErrors();

        $driver->refresh();
        $this->assertSame('Khan', $driver->last_name);
        $this->assertSame('ali.khan@example.com', $driver->email);
        $this->assertTrue($driver->isProfileCompleteForAgreement());
    }

    public function test_store_requires_payment_method_when_amount_paid_is_positive(): void
    {
        $response = $this->from(route('reservations.create'))
            ->post(route('reservations.store'), [
                'driver_mode' => 'new',
                'first_name' => 'Ali',
                'reservation_date' => '2026-06-25',
                'pick_up_date' => '2026-06-30',
                'agreed_rent' => 100,
                'agreed_advance' => 50,
                'amount_paid' => 25,
            ]);

        $response->assertRedirect(route('reservations.create'));
        $response->assertSessionHasErrors('payment_method');
    }

    public function test_store_requires_bank_account_for_bank_transfer(): void
    {
        $companyId = $this->createCompanyAndBankAccount();

        $response = $this->from(route('reservations.create'))
            ->post(route('reservations.store'), [
                'driver_mode' => 'new',
                'first_name' => 'Ali',
                'reservation_date' => '2026-06-25',
                'pick_up_date' => '2026-06-30',
                'agreed_rent' => 100,
                'agreed_advance' => 50,
                'amount_paid' => 25,
                'payment_method' => 'Bank Transfer',
            ]);

        $response->assertRedirect(route('reservations.create'));
        $response->assertSessionHasErrors('bank_account_id');
        $this->assertDatabaseCount('car_reservations', 0);
    }

    public function test_store_with_zero_amount_paid_does_not_require_payment_method(): void
    {
        $response = $this->post(route('reservations.store'), [
            'driver_mode' => 'new',
            'first_name' => 'Ali',
            'reservation_date' => '2026-06-25',
            'pick_up_date' => '2026-06-30',
            'agreed_rent' => 100,
            'agreed_advance' => 50,
            'amount_paid' => 0,
        ]);

        $response->assertRedirect(route('reservations.index'));
        $response->assertSessionHasNoErrors();

        $reservation = CarReservation::query()->first();
        $this->assertNotNull($reservation);
        $this->assertNull($reservation->payment_method);
        $this->assertNull($reservation->bank_account_id);
    }

    public function test_store_persists_payment_method_and_bank_account(): void
    {
        $bankAccountId = $this->createCompanyAndBankAccount();

        $response = $this->post(route('reservations.store'), [
            'driver_mode' => 'new',
            'first_name' => 'Ali',
            'reservation_date' => '2026-06-25',
            'pick_up_date' => '2026-06-30',
            'agreed_rent' => 100,
            'agreed_advance' => 50,
            'amount_paid' => 25,
            'payment_method' => 'Bank Transfer',
            'bank_account_id' => $bankAccountId,
        ]);

        $response->assertRedirect(route('reservations.index'));
        $response->assertSessionHasNoErrors();

        $reservation = CarReservation::query()->first();
        $this->assertNotNull($reservation);
        $this->assertSame('Bank Transfer', $reservation->payment_method);
        $this->assertSame($bankAccountId, $reservation->bank_account_id);
    }

    public function test_update_persists_payment_method_and_bank_account(): void
    {
        $driver = Driver::query()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Ali',
            'last_name' => 'Khan',
            'dob' => '1990-05-15',
            'email' => 'ali.khan@example.com',
            'phone_number' => '07000000007',
            'address1' => '10 Test Road',
            'post_code' => 'E1 1AA',
            'town' => 'London',
            'country_id' => 1,
            'driver_license_number' => 'LIC000777',
            'driver_license_expiry_date' => '2027-12-31',
            'next_of_kin' => 'Sara Khan',
            'next_of_kin_phone' => '07000000008',
        ]);

        $bankAccountId = $this->createCompanyAndBankAccount();

        $reservation = CarReservation::query()->create([
            'tenant_id' => $this->tenant->id,
            'driver_id' => $driver->id,
            'customer_name' => 'Ali Khan',
            'reservation_date' => '2026-06-25',
            'pick_up_date' => '2026-06-30',
            'agreed_rent' => 100,
            'agreed_advance' => 50,
            'amount_paid' => 0,
            'balance_payable_on_pickup' => 150,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $response = $this->from(route('reservations.edit', $reservation))
            ->put(route('reservations.update', $reservation), [
                'driver_mode' => 'existing',
                'driver_id' => $driver->id,
                'first_name' => 'Ali',
                'last_name' => 'Khan',
                'dob' => '1990-05-15',
                'email' => 'ali.khan@example.com',
                'phone_number' => '07000000007',
                'address1' => '10 Test Road',
                'post_code' => 'E1 1AA',
                'town' => 'London',
                'country_id' => 1,
                'driver_license_number' => 'LIC000777',
                'driver_license_expiry_date' => '2027-12-31',
                'next_of_kin' => 'Sara Khan',
                'next_of_kin_phone' => '07000000008',
                'reservation_date' => '2026-06-25',
                'pick_up_date' => '2026-06-30',
                'agreed_rent' => 100,
                'agreed_advance' => 50,
                'amount_paid' => 30,
                'payment_method' => 'Bank Transfer',
                'bank_account_id' => $bankAccountId,
            ]);

        $response->assertRedirect(route('reservations.index'));
        $response->assertSessionHasNoErrors();

        $reservation->refresh();
        $this->assertSame('Bank Transfer', $reservation->payment_method);
        $this->assertSame($bankAccountId, $reservation->bank_account_id);
    }

    private function createCompanyAndBankAccount(): int
    {
        $companyId = DB::table('companies')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Co',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('bank_accounts')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'company_id' => $companyId,
            'bank_name' => 'Test Bank',
            'account_number' => '12345678',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function setUpReservationDatabase(): void
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

        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        DB::table('countries')->insert([
            'id' => 1,
            'name' => 'United Kingdom',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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
            $table->unsignedBigInteger('createdBy')->nullable();
            $table->unsignedBigInteger('updatedBy')->nullable();
            $table->timestamps();
        });

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->date('dob')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone_number')->nullable();
            $table->string('ni_number')->nullable();
            $table->string('address1')->nullable();
            $table->string('address2')->nullable();
            $table->string('post_code')->nullable();
            $table->string('town')->nullable();
            $table->string('county')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->string('driver_license_number')->nullable();
            $table->date('driver_license_expiry_date')->nullable();
            $table->string('phd_license_number')->nullable();
            $table->date('phd_license_expiry_date')->nullable();
            $table->string('next_of_kin')->nullable();
            $table->string('next_of_kin_phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('createdBy')->nullable();
            $table->unsignedBigInteger('updatedBy')->nullable();
            $table->timestamps();
        });

        Schema::create('car_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->unsignedBigInteger('car_id')->nullable();
            $table->foreignId('driver_id')->nullable();
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->date('reservation_date');
            $table->date('pick_up_date')->nullable();
            $table->date('available_from_date')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->string('status')->default('active');
            $table->decimal('agreed_rent', 12, 2)->nullable();
            $table->decimal('agreed_advance', 12, 2)->nullable();
            $table->decimal('amount_paid', 12, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->decimal('balance_payable_on_pickup', 12, 2)->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });
    }
}
