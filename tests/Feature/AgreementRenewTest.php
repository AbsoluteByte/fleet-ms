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
use App\Services\AgreementUpgradeService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\Concerns\SetupAgreementChangeCarDatabase;
use Tests\TestCase;

class AgreementRenewTest extends TestCase
{
    use SetupAgreementChangeCarDatabase;

    private Tenant $tenant;

    private Company $company;

    private Driver $driver;

    private Car $car;

    private Status $activeStatus;

    private Status $expiredStatus;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpAgreementChangeCarDatabase();
        $this->setUpHttpTestExtras();

        Carbon::setTestNow(Carbon::parse('2026-08-17 10:00:00'));

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Renew Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $this->company = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Renew Company',
        ]);
        $this->driver = Driver::query()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Renew',
            'last_name' => 'Driver',
            'email' => 'renew@example.com',
            'phone_number' => '07000000012',
            'is_active' => true,
        ]);

        $carModelId = (int) DB::table('car_models')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'name' => 'Renew Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->car = Car::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'car_model_id' => $carModelId,
            'registration' => 'REN-01',
            'color' => 'White',
            'fleet_status' => Car::FLEET_STATUS_ON_RENT,
        ]);

        $this->activeStatus = Status::query()->create(['name' => 'Active', 'type' => 'agreement']);
        $this->expiredStatus = Status::query()->create(['name' => 'Expired', 'type' => 'agreement']);

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
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        $this->tearDownAgreementChangeCarDatabase();

        parent::tearDown();
    }

    public function test_renew_creates_active_successor_and_stops_old_billing(): void
    {
        $old = Agreement::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $this->car->id,
            'start_date' => '2025-07-07 09:00:00',
            'end_date' => '2026-07-07',
            'agreed_rent' => 250,
            'rent_interval' => 'Weekly',
            'deposit_amount' => 500,
            'collection_type' => 'weekly',
            'status_id' => $this->expiredStatus->id,
        ]);

        app(AgreementInvoiceService::class)->generateForAgreement($old->fresh(['status']), Carbon::parse('2026-08-17'));
        $this->assertTrue(
            Invoice::query()->where('source_id', $old->id)->where('invoice_type', 'agreement')->exists()
        );

        $response = $this->from(route('agreements.renew', $old))
            ->post(route('agreements.renew.store', $old), [
                'start_date' => '2026-08-17T09:00',
                'end_date' => '2027-08-17',
            ]);

        $new = Agreement::query()->where('renewed_from_agreement_id', $old->id)->first();
        $this->assertNotNull($new);
        $response->assertRedirect(route('agreements.show', $new));

        $old->refresh();
        $this->assertSame($this->expiredStatus->id, $old->status_id);
        $this->assertSame('2026-08-17 09:00:00', $old->closing_date->format('Y-m-d H:i:s'));
        $this->assertTrue($old->hasBeenRenewed());
        $this->assertFalse($old->fresh(['status'])->isBillableStatus());
        $this->assertFalse($old->canRequestDepositRefund());

        $this->assertSame($this->driver->id, $new->driver_id);
        $this->assertSame($this->car->id, $new->car_id);
        $this->assertSame($this->activeStatus->id, $new->status_id);
        $this->assertSame('2027-08-17', $new->end_date->toDateString());
        $this->assertEquals(250.0, (float) $new->agreed_rent);
        $this->assertEquals(500.0, (float) $new->deposit_amount);

        $generatedAfterClose = app(AgreementInvoiceService::class)->generateForAgreement(
            $old->fresh(['status']),
            Carbon::parse('2026-08-24')
        );
        $this->assertSame(0, $generatedAfterClose);
    }

    public function test_renew_form_loads_for_expired_agreement(): void
    {
        $old = Agreement::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $this->car->id,
            'start_date' => '2025-07-07 09:00:00',
            'end_date' => '2026-07-07',
            'agreed_rent' => 250,
            'rent_interval' => 'Weekly',
            'deposit_amount' => 500,
            'collection_type' => 'weekly',
            'status_id' => $this->expiredStatus->id,
        ]);

        $this->assertTrue(app(AgreementUpgradeService::class)->canRenew($old->fresh(['status'])));

        $response = $this->get(route('agreements.renew', $old));

        $response->assertOk();
        $response->assertSee('Renew Agreement');
        $response->assertSee('New start date');
        $response->assertSee('New expiry date');
        $response->assertSee('REN-01');
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
    }
}
