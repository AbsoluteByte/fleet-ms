<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Car;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Status;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\Concerns\SetupAgreementChangeCarDatabase;
use Tests\TestCase;

class AgreementPayingCompanyNameTest extends TestCase
{
    use SetupAgreementChangeCarDatabase;

    private Tenant $tenant;

    private Company $company;

    private Driver $driver;

    private Car $car;

    private Status $activeStatus;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpAgreementChangeCarDatabase();
        $this->setUpHttpTestExtras();

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Paying Company Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $this->company = Company::query()->create(['tenant_id' => $this->tenant->id, 'name' => 'Fleet Co']);
        $this->driver = Driver::query()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'John',
            'last_name' => 'Client',
            'email' => 'john-client@example.com',
            'phone_number' => '07000000003',
            'dob' => '1990-01-01',
            'address1' => '1 Test Street',
            'post_code' => 'SW1A 1AA',
            'town' => 'London',
            'country_id' => 1,
            'driver_license_number' => 'DL123456',
            'driver_license_expiry_date' => '2027-01-01',
            'next_of_kin' => 'Jane Client',
            'next_of_kin_phone' => '07000000004',
            'is_active' => true,
        ]);

        $carModelId = (int) DB::table('car_models')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $counselId = (int) DB::table('counsels')->insertGetId([
            'name' => 'Test Counsel',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->car = $this->createCompliantCar($this->tenant->id, $this->company->id, $carModelId, $counselId);
        $this->activeStatus = Status::query()->create(['name' => 'Active', 'type' => 'agreement']);

        $this->user = User::factory()->create();
        DB::table('model_has_roles')->insert([
            'role_id' => (int) DB::table('roles')->value('id'),
            'model_type' => User::class,
            'model_id' => $this->user->id,
        ]);
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
        Schema::dropIfExists('deposit_refunds');
        Schema::dropIfExists('agreement_deductions');
        Schema::dropIfExists('countries');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('agreement_collections');
        Schema::dropIfExists('car_status_histories');
        Schema::dropIfExists('tenant_user');
        $this->tearDownAgreementChangeCarDatabase();

        parent::tearDown();
    }

    public function test_store_saves_paying_company_name(): void
    {
        $response = $this->from(route('agreements.create'))
            ->post(route('agreements.store'), $this->basePayload([
                'paying_company_name' => 'ABC Transport Ltd',
            ]));

        $response->assertRedirect(route('agreements.index'));
        $response->assertSessionHasNoErrors();

        $agreement = Agreement::query()->latest('id')->firstOrFail();
        $this->assertSame('ABC Transport Ltd', $agreement->paying_company_name);
    }

    public function test_show_displays_paying_company_name_when_set(): void
    {
        $agreement = $this->createAgreement([
            'paying_company_name' => 'XYZ Holdings',
        ]);

        $response = $this->get(route('agreements.show', $agreement));

        $response->assertOk();
        $response->assertSee('Paying company:', false);
        $response->assertSee('XYZ Holdings', false);
    }

    public function test_show_hides_paying_company_row_when_not_set(): void
    {
        $agreement = $this->createAgreement([
            'paying_company_name' => null,
        ]);

        $response = $this->get(route('agreements.show', $agreement));

        $response->assertOk();
        $response->assertDontSee('Paying company:', false);
    }

    public function test_index_displays_pays_via_line_when_set(): void
    {
        $this->createAgreement([
            'paying_company_name' => 'Metro Cars PLC',
        ]);

        $response = $this->get(route('agreements.index'));

        $response->assertOk();
        $response->assertSee('Pays via: Metro Cars PLC', false);
    }

    public function test_paying_company_name_longer_than_255_characters_is_rejected(): void
    {
        $response = $this->from(route('agreements.create'))
            ->post(route('agreements.store'), $this->basePayload([
                'paying_company_name' => str_repeat('A', 256),
            ]));

        $response->assertSessionHasErrors('paying_company_name');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function basePayload(array $overrides = []): array
    {
        return array_merge([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $this->car->id,
            'start_date' => '2026-06-01T09:00',
            'end_date' => '2027-06-01',
            'agreed_rent' => 150,
            'rent_interval' => 'Weekly',
            'collection_type' => 'weekly',
            'deposit_amount' => 200,
            'status_id' => $this->activeStatus->id,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createAgreement(array $overrides = []): Agreement
    {
        return Agreement::query()->create(array_merge([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $this->car->id,
            'start_date' => '2026-06-01',
            'end_date' => '2027-06-01',
            'agreed_rent' => 150,
            'rent_interval' => 'Weekly',
            'deposit_amount' => 200,
            'collection_type' => 'weekly',
            'status_id' => $this->activeStatus->id,
        ], $overrides));
    }

    private function setUpHttpTestExtras(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->default(1);
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

        Schema::table('drivers', function (Blueprint $table) {
            $table->date('dob')->nullable();
            $table->string('address1')->nullable();
            $table->string('post_code')->nullable();
            $table->string('town')->nullable();
            $table->foreignId('country_id')->nullable();
            $table->string('driver_license_number')->nullable();
            $table->date('driver_license_expiry_date')->nullable();
            $table->string('next_of_kin')->nullable();
            $table->string('next_of_kin_phone')->nullable();
            $table->boolean('is_active')->default(true);
        });

        Schema::table('agreements', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_agreement_id')->nullable();
            $table->string('paying_company_name')->nullable();
            $table->json('mutual_detail_slip_document')->nullable();
        });

        Schema::table('car_reservations', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable();
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
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        DB::table('roles')->insert([
            'name' => 'admin',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('car_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('car_id');
            $table->string('previous_status')->nullable();
            $table->string('new_status');
            $table->json('status_data')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamps();
        });

        Schema::create('agreement_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id');
            $table->date('date');
            $table->date('due_date')->nullable();
            $table->string('method');
            $table->decimal('amount', 10, 2);
            $table->string('payment_status')->default('pending');
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_auto_generated')->default(false);
            $table->timestamps();
        });

        Schema::create('agreement_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('agreement_id');
            $table->decimal('amount', 12, 2);
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('deposit_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('agreement_id')->unique();
            $table->foreignId('driver_id');
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('gross_deposit_amount', 12, 2)->default(0);
            $table->decimal('deductions_amount', 12, 2)->default(0);
            $table->decimal('debt_offset_amount', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('status')->default('pending');
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
    }

    private function createCompliantCar(int $tenantId, int $companyId, int $carModelId, int $counselId): Car
    {
        $car = Car::query()->create([
            'tenant_id' => $tenantId,
            'company_id' => $companyId,
            'car_model_id' => $carModelId,
            'registration' => 'PC123',
            'color' => 'Black',
            'vin' => 'VINPC123',
            'manufacture_year' => 2020,
            'registration_year' => 2020,
            'purchase_date' => '2020-01-01',
            'purchase_price' => 10000,
            'purchase_type' => 'uk',
            'fleet_status' => Car::FLEET_STATUS_AVAILABLE_FOR_RENT,
            'sorn_applied' => false,
        ]);

        DB::table('car_mots')->insert([
            'car_id' => $car->id,
            'expiry_date' => '2027-01-01',
            'amount' => 50,
            'term' => '12 months',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('car_road_taxes')->insert([
            'car_id' => $car->id,
            'start_date' => '2026-01-01',
            'term' => '12 months',
            'amount' => 200,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('car_phvs')->insert([
            'car_id' => $car->id,
            'counsel_id' => $counselId,
            'amount' => 200,
            'start_date' => '2026-01-01',
            'expiry_date' => '2027-01-01',
            'notify_before_expiry' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $car;
    }
}
