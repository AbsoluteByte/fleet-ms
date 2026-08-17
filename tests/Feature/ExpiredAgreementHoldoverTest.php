<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Car;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\Status;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AgreementInvoiceService;
use App\Services\CarFleetRentStatusService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\Concerns\SetupAgreementChangeCarDatabase;
use Tests\TestCase;

class ExpiredAgreementHoldoverTest extends TestCase
{
    use SetupAgreementChangeCarDatabase;

    private Tenant $tenant;

    private Company $company;

    private Driver $driver;

    private Car $car;

    private Status $activeStatus;

    private Status $expiredStatus;

    private Status $terminatedStatus;

    private Status $replacementStatus;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpAgreementChangeCarDatabase();
        $this->setUpHttpTestExtras();

        Carbon::setTestNow(Carbon::parse('2026-08-17 10:00:00'));

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Holdover Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $this->company = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Holdover Company',
        ]);
        $this->driver = Driver::query()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Hold',
            'last_name' => 'Over',
            'email' => 'holdover@example.com',
            'phone_number' => '07000000011',
            'is_active' => true,
        ]);

        $carModelId = (int) DB::table('car_models')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'name' => 'Holdover Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->car = Car::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'car_model_id' => $carModelId,
            'registration' => 'HOLD-01',
            'color' => 'Black',
            'fleet_status' => Car::FLEET_STATUS_ON_RENT,
        ]);

        $this->activeStatus = Status::query()->create(['name' => 'Active', 'type' => 'agreement']);
        $this->expiredStatus = Status::query()->create(['name' => 'Expired', 'type' => 'agreement']);
        $this->terminatedStatus = Status::query()->create(['name' => 'Terminated', 'type' => 'agreement']);
        $this->replacementStatus = Status::query()->create(['name' => 'Replacement Vehicle', 'type' => 'agreement']);

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
        Carbon::setTestNow();
        Schema::dropIfExists('deposit_refunds');
        Schema::dropIfExists('agreement_deductions');
        Schema::dropIfExists('agreement_collections');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        $this->tearDownAgreementChangeCarDatabase();

        parent::tearDown();
    }

    public function test_mark_expired_command_flips_active_past_end_date(): void
    {
        $agreement = $this->createAgreement([
            'status_id' => $this->activeStatus->id,
            'end_date' => '2026-08-10',
        ]);
        $future = $this->createAgreement([
            'status_id' => $this->activeStatus->id,
            'end_date' => '2026-09-01',
        ]);
        $terminated = $this->createAgreement([
            'status_id' => $this->terminatedStatus->id,
            'end_date' => '2026-08-10',
            'closing_date' => '2026-08-10 11:00:00',
        ]);
        $replacement = $this->createAgreement([
            'status_id' => $this->replacementStatus->id,
            'end_date' => '2026-08-10',
        ]);

        Artisan::call('agreements:mark-expired');

        $this->assertSame($this->expiredStatus->id, $agreement->fresh()->status_id);
        $this->assertNull($agreement->fresh()->closing_date);
        $this->assertSame($this->activeStatus->id, $future->fresh()->status_id);
        $this->assertSame($this->terminatedStatus->id, $terminated->fresh()->status_id);
        $this->assertSame($this->replacementStatus->id, $replacement->fresh()->status_id);
    }

    public function test_expired_holdover_keeps_generating_invoices_and_on_hire(): void
    {
        $agreement = $this->createAgreement([
            'status_id' => $this->expiredStatus->id,
            'start_date' => '2026-07-01 09:00:00',
            'end_date' => '2026-08-01',
            'agreed_rent' => 100,
            'rent_interval' => 'Weekly',
        ]);

        $generated = app(AgreementInvoiceService::class)->generateDueInvoices(Carbon::parse('2026-08-17'));

        $this->assertGreaterThan(0, $generated);
        $this->assertTrue(
            Invoice::query()
                ->where('source_id', $agreement->id)
                ->where('invoice_type', 'agreement')
                ->whereDate('invoice_date', '>', '2026-08-01')
                ->exists()
        );

        app(CarFleetRentStatusService::class)->syncForAgreement($agreement->fresh());
        $this->assertSame(Car::FLEET_STATUS_ON_RENT, $this->car->fresh()->fleet_status);
        $this->assertTrue($agreement->fresh(['status'])->isBillableStatus());
        $this->assertFalse($agreement->fresh(['status'])->isClosedForDepositRefund());
    }

    public function test_terminated_agreement_does_not_generate_holdover_invoices(): void
    {
        $agreement = $this->createAgreement([
            'status_id' => $this->terminatedStatus->id,
            'start_date' => '2026-07-01 09:00:00',
            'end_date' => '2026-08-01',
            'closing_date' => '2026-08-01 11:00:00',
            'agreed_rent' => 100,
            'rent_interval' => 'Weekly',
        ]);

        $generated = app(AgreementInvoiceService::class)->generateDueInvoices(Carbon::parse('2026-08-17'));

        $this->assertSame(0, $generated);
        $this->assertFalse(
            Invoice::query()->where('source_id', $agreement->id)->exists()
        );
    }

    public function test_agreements_index_includes_expired_between_filter(): void
    {
        $this->createAgreement([
            'status_id' => $this->expiredStatus->id,
            'end_date' => '2026-08-10',
        ]);

        $response = $this->get(route('agreements.index'));

        $response->assertOk();
        $response->assertSee('Expired between');
        $response->assertSee('Terminated only');
        $response->assertSee('data-status="Expired"', false);
        $response->assertSee('Renew Agreement');
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
            'start_date' => '2026-07-01 09:00:00',
            'end_date' => '2026-08-10',
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
            $table->foreignId('agreement_id');
            $table->decimal('amount', 10, 2);
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('deposit_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id');
            $table->timestamps();
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->string('bank_name')->nullable();
            $table->timestamps();
        });
    }
}
