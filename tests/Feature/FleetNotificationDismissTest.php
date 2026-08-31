<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Company;
use App\Models\FleetNotificationDismissal;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\Concerns\SetupAgreementChangeCarDatabase;
use Tests\TestCase;

class FleetNotificationDismissTest extends TestCase
{
    use SetupAgreementChangeCarDatabase;

    private Tenant $tenant;

    private Company $company;

    private User $jawad;

    private User $otherUser;

    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpAgreementChangeCarDatabase();
        $this->setUpDismissTestExtras();

        Carbon::setTestNow(Carbon::parse('2026-07-20 10:00:00'));

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Dismiss Notify Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $this->company = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Dismiss Notify Company',
        ]);

        $carModelId = DB::table('car_models')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'name' => 'Dismiss Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $counselId = DB::table('counsels')->insertGetId([
            'name' => 'Dismiss Counsel',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->car = $this->createCarWithExpiringPhv('DSM001', (int) $carModelId, (int) $counselId);

        $this->jawad = User::factory()->create(['email' => 'jawad@samoretraders.com']);
        $this->otherUser = User::factory()->create(['email' => 'other@example.com']);

        foreach ([$this->jawad, $this->otherUser] as $user) {
            $user->tenants()->attach($this->tenant->id, [
                'role' => 'admin',
                'is_primary' => true,
                'joined_at' => now(),
            ]);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Schema::dropIfExists('fleet_notification_dismissals');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('car_services');
        Schema::dropIfExists('car_status_histories');
        Schema::dropIfExists('agreement_collections');
        $this->tearDownAgreementChangeCarDatabase();
        parent::tearDown();
    }

    public function test_jawad_can_dismiss_phv_notification(): void
    {
        $this->actingAs($this->jawad);
        $this->jawad->switchTenant($this->tenant->id);

        $notification = $this->phvNotificationForCar();
        $this->assertNotNull($notification);

        $response = $this->postJson(route('notifications.dismiss'), [
            'notification_id' => $notification['id'],
            'notification_type' => $notification['type'],
            'source_record_id' => $notification['source_record_id'],
            'sort_key' => $notification['sort_key'],
            'car_id' => $notification['car_id'],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('summary.expiring_phv', 0);

        $this->assertNull($this->phvNotificationForCar());
        $this->assertDatabaseHas('fleet_notification_dismissals', [
            'user_id' => $this->jawad->id,
            'tenant_id' => $this->tenant->id,
            'notification_type' => 'phv_expiry',
            'car_id' => $this->car->id,
        ]);
    }

    public function test_other_user_still_sees_notification_after_jawad_dismisses(): void
    {
        $this->actingAs($this->jawad);
        $this->jawad->switchTenant($this->tenant->id);

        $notification = $this->phvNotificationForCar();
        $this->assertNotNull($notification);

        $this->postJson(route('notifications.dismiss'), [
            'notification_id' => $notification['id'],
            'notification_type' => $notification['type'],
            'source_record_id' => $notification['source_record_id'],
            'sort_key' => $notification['sort_key'],
            'car_id' => $notification['car_id'],
        ])->assertOk();

        $this->actingAs($this->otherUser);
        $this->otherUser->switchTenant($this->tenant->id);

        $this->assertNotNull($this->phvNotificationForCar());
    }

    public function test_phv_notification_reappears_after_renewal(): void
    {
        $this->actingAs($this->jawad);
        $this->jawad->switchTenant($this->tenant->id);

        $notification = $this->phvNotificationForCar();
        $this->assertNotNull($notification);

        $this->postJson(route('notifications.dismiss'), [
            'notification_id' => $notification['id'],
            'notification_type' => $notification['type'],
            'source_record_id' => $notification['source_record_id'],
            'sort_key' => $notification['sort_key'],
            'car_id' => $notification['car_id'],
        ])->assertOk();

        $this->assertNull($this->phvNotificationForCar());

        $counselId = DB::table('counsels')->value('id');

        DB::table('car_phvs')->insert([
            'car_id' => $this->car->id,
            'counsel_id' => $counselId,
            'amount' => 200,
            'start_date' => '2026-07-21',
            'expiry_date' => '2026-07-28',
            'notify_before_expiry' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $renewedNotification = $this->phvNotificationForCar();

        $this->assertNotNull($renewedNotification);
        $this->assertNotSame($notification['id'], $renewedNotification['id']);
    }

    public function test_non_jawad_cannot_dismiss_notification(): void
    {
        $this->actingAs($this->otherUser);
        $this->otherUser->switchTenant($this->tenant->id);

        $notification = $this->phvNotificationForCar();
        $this->assertNotNull($notification);

        $this->postJson(route('notifications.dismiss'), [
            'notification_id' => $notification['id'],
            'notification_type' => $notification['type'],
            'source_record_id' => $notification['source_record_id'],
            'sort_key' => $notification['sort_key'],
            'car_id' => $notification['car_id'],
        ])->assertForbidden();

        $this->assertSame(0, FleetNotificationDismissal::query()->count());
    }

    public function test_summary_counts_exclude_dismissed_phv_for_jawad(): void
    {
        $this->actingAs($this->jawad);
        $this->jawad->switchTenant($this->tenant->id);

        $before = $this->getJson(route('dashboard.fleet-notifications'));
        $before->assertOk();
        $this->assertSame(1, $before->json('summary.expiring_phv'));

        $notification = $this->phvNotificationForCar();
        $this->assertNotNull($notification);

        $afterDismiss = $this->postJson(route('notifications.dismiss'), [
            'notification_id' => $notification['id'],
            'notification_type' => $notification['type'],
            'source_record_id' => $notification['source_record_id'],
            'sort_key' => $notification['sort_key'],
            'car_id' => $notification['car_id'],
        ]);

        $afterDismiss->assertOk()
            ->assertJsonPath('summary.expiring_phv', 0);

        $after = $this->getJson(route('dashboard.fleet-notifications'));
        $after->assertOk();
        $this->assertSame(0, $after->json('summary.expiring_phv'));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function phvNotificationForCar(): ?array
    {
        $response = $this->getJson(route('notifications.index', ['type' => 'phv_expiry']), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();

        return collect($response->json('data'))
            ->first(fn (array $notification) => (int) ($notification['car_id'] ?? 0) === (int) $this->car->id);
    }

    private function createCarWithExpiringPhv(string $registration, int $carModelId, int $counselId): Car
    {
        $car = Car::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'car_model_id' => $carModelId,
            'registration' => $registration,
            'color' => 'Black',
            'vin' => uniqid('VIN', true),
            'manufacture_year' => 2020,
            'registration_year' => 2020,
            'purchase_date' => '2020-01-01',
            'purchase_price' => 10000,
            'purchase_type' => 'uk',
            'fleet_status' => 'rented',
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
            'amount' => 180,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('car_phvs')->insert([
            'car_id' => $car->id,
            'counsel_id' => $counselId,
            'amount' => 200,
            'start_date' => '2026-01-01',
            'expiry_date' => '2026-07-25',
            'notify_before_expiry' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $car;
    }

    private function setUpDismissTestExtras(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'status')) {
                $table->unsignedTinyInteger('status')->default(1);
            }
        });

        Schema::table('drivers', function (Blueprint $table) {
            if (! Schema::hasColumn('drivers', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (! Schema::hasColumn('drivers', 'driver_license_expiry_date')) {
                $table->date('driver_license_expiry_date')->nullable();
            }
            if (! Schema::hasColumn('drivers', 'phd_license_expiry_date')) {
                $table->date('phd_license_expiry_date')->nullable();
            }
        });

        Schema::table('car_insurances', function (Blueprint $table) {
            if (! Schema::hasColumn('car_insurances', 'status_id')) {
                $table->foreignId('status_id')->nullable();
            }
            if (! Schema::hasColumn('car_insurances', 'applied_date')) {
                $table->date('applied_date')->nullable();
            }
            if (! Schema::hasColumn('car_insurances', 'start_date')) {
                $table->date('start_date')->nullable();
            }
            if (! Schema::hasColumn('car_insurances', 'expiry_date')) {
                $table->date('expiry_date')->nullable();
            }
            if (! Schema::hasColumn('car_insurances', 'notify_before_expiry')) {
                $table->integer('notify_before_expiry')->default(30);
            }
        });

        Schema::create('car_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id');
            $table->date('service_date');
            $table->decimal('amount', 10, 2)->default(0);
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

        Schema::create('fleet_notification_dismissals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('tenant_id');
            $table->string('notification_type');
            $table->foreignId('car_id')->nullable();
            $table->foreignId('driver_id')->nullable();
            $table->unsignedBigInteger('source_record_id');
            $table->date('source_expiry_date')->nullable();
            $table->timestamps();

            $table->unique(
                ['user_id', 'notification_type', 'source_record_id', 'source_expiry_date'],
                'fleet_notification_dismissals_unique'
            );
        });
    }
}
