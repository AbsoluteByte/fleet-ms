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

class AgreementsIndexTerminationNoticeFilterTest extends TestCase
{
    use SetupAgreementChangeCarDatabase;

    private Tenant $tenant;

    private Company $company;

    private Driver $driver;

    private Car $car;

    private Status $activeStatus;

    private Status $swapStatus;

    private Status $terminatedStatus;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpAgreementChangeCarDatabase();
        $this->setUpHttpTestExtras();

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Agreements Termination Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $this->company = Company::query()->create(['tenant_id' => $this->tenant->id, 'name' => 'Fleet Co']);
        $this->driver = Driver::query()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Sam',
            'last_name' => 'Driver',
            'email' => 'sam-driver-agreements@example.com',
            'phone_number' => '07000000007',
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

        $this->car = $this->createCar($this->tenant->id, $this->company->id, $carModelId, $counselId);
        $this->activeStatus = Status::query()->create(['name' => 'Active', 'type' => 'agreement']);
        $this->swapStatus = Status::query()->create(['name' => 'Swap', 'type' => 'agreement']);
        $this->terminatedStatus = Status::query()->create(['name' => 'Terminated', 'type' => 'agreement']);

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
        Schema::dropIfExists('deposit_refunds');
        Schema::dropIfExists('agreement_deductions');
        Schema::dropIfExists('agreement_collections');
        Schema::dropIfExists('car_status_histories');
        Schema::dropIfExists('car_services');
        Schema::dropIfExists('agreement_collections');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('tenant_user');
        $this->tearDownAgreementChangeCarDatabase();

        parent::tearDown();
    }

    public function test_agreements_index_includes_notice_filter_data_for_active_agreement(): void
    {
        $this->createAgreement([
            'status_id' => $this->activeStatus->id,
            'termination_notice_date' => '2026-07-10',
        ]);

        $response = $this->get(route('agreements.index'));

        $response->assertOk();
        $response->assertSee('data-notice-date="2026-07-10"', false);
        $response->assertSee('data-is-billable="1"', false);
        $response->assertSee('agreementsHasNotice', false);
        $response->assertSee('Active or Swap agreements only.', false);
    }

    public function test_agreements_index_includes_export_controls(): void
    {
        $this->createAgreement([
            'status_id' => $this->activeStatus->id,
        ]);

        $response = $this->get(route('agreements.index'));

        $response->assertOk();
        $response->assertSee('id="agreementsExportDropdown"', false);
        $response->assertSee('id="agreementsExportCsv"', false);
        $response->assertSee('id="agreementsExportPdf"', false);
        $response->assertSee('pdfmake.min.js', false);
        $response->assertSee('exportAgreementsCsv', false);
        $response->assertSee('exportAgreementsPdf', false);
    }

    public function test_agreements_index_includes_notice_filter_data_for_swap_agreement(): void
    {
        $this->createAgreement([
            'status_id' => $this->swapStatus->id,
            'termination_notice_date' => '2026-07-15',
        ]);

        $response = $this->get(route('agreements.index'));

        $response->assertOk();
        $response->assertSee('data-notice-date="2026-07-15"', false);
    }

    public function test_agreements_index_ignores_notice_filter_data_for_terminated_agreement(): void
    {
        $this->createAgreement([
            'status_id' => $this->terminatedStatus->id,
            'termination_notice_date' => '2026-07-10',
        ]);

        $response = $this->get(route('agreements.index'));

        $response->assertOk();
        $response->assertSee('data-notice-date=""', false);
        $response->assertSee('data-is-billable="0"', false);
        $response->assertSee('Jul 10, 2026', false);
    }

    public function test_expired_agreement_is_excluded_from_notice_filter_data(): void
    {
        $expiredStatus = Status::query()->create(['name' => 'Expired', 'type' => 'agreement']);

        $this->createAgreement([
            'status_id' => $expiredStatus->id,
            'termination_notice_date' => '2026-07-12',
        ]);

        $response = $this->get(route('agreements.index'));

        $response->assertOk();
        $response->assertSee('data-notice-date=""', false);
        $response->assertSee('data-is-billable="0"', false);
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

        Schema::table('car_insurances', function (Blueprint $table) {
            $table->foreignId('status_id')->nullable();
        });

        Schema::create('car_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id');
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

    private function createCar(int $tenantId, int $companyId, int $carModelId, int $counselId): Car
    {
        $car = Car::query()->create([
            'tenant_id' => $tenantId,
            'company_id' => $companyId,
            'car_model_id' => $carModelId,
            'registration' => 'TN123',
            'color' => 'Black',
            'vin' => 'VINTN123',
            'manufacture_year' => 2020,
            'registration_year' => 2020,
            'purchase_date' => '2020-01-01',
            'purchase_price' => 10000,
            'purchase_type' => 'uk',
            'fleet_status' => Car::FLEET_STATUS_AVAILABLE_FOR_RENT,
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
            'amount' => 180,
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
