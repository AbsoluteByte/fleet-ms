<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Car;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Status;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\Concerns\SetupAgreementChangeCarDatabase;
use Tests\TestCase;

class AgreementFleetNotificationTest extends TestCase
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

        Carbon::setTestNow(Carbon::parse('2026-07-20 10:00:00'));

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Agreement Notify Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $this->company = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Agreement Notify Company',
        ]);
        $this->driver = Driver::query()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Notify',
            'last_name' => 'Driver',
            'email' => 'notify@example.com',
            'phone_number' => '07000000099',
        ]);

        $carModelId = DB::table('car_models')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'name' => 'Notify Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $counselId = DB::table('counsels')->insertGetId([
            'name' => 'Notify Counsel',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->car = $this->createCompliantCar('NTF001', $carModelId, $counselId, 'rented');

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
        Carbon::setTestNow();
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('car_services');
        Schema::dropIfExists('car_status_histories');
        Schema::dropIfExists('agreement_collections');
        $this->tearDownAgreementChangeCarDatabase();
        parent::tearDown();
    }

    public function test_active_agreement_with_end_date_in_five_days_is_included(): void
    {
        $agreement = $this->createAgreement([
            'end_date' => '2026-07-25',
            'status_id' => $this->activeStatus->id,
        ]);

        $notification = $this->agreementNotificationFor($agreement->id);

        $this->assertNotNull($notification);
        $this->assertSame('agreement_end_date', $notification['type']);
        $this->assertSame('Agreement Ending Soon', $notification['title']);
        $this->assertStringContainsString('NTF001', $notification['simple_message']);
        $this->assertStringContainsString('Ends in 5 days', $notification['simple_message']);
    }

    public function test_active_agreement_with_termination_notice_in_three_days_is_included(): void
    {
        $agreement = $this->createAgreement([
            'end_date' => '2027-07-20',
            'termination_notice_date' => '2026-07-23',
            'status_id' => $this->activeStatus->id,
        ]);

        $notification = $this->agreementNotificationFor($agreement->id);

        $this->assertNotNull($notification);
        $this->assertSame('agreement_termination_notice', $notification['type']);
        $this->assertSame('Termination Notice Due', $notification['title']);
        $this->assertStringContainsString('Termination notice in 3 days', $notification['simple_message']);
    }

    public function test_both_dates_qualify_produces_one_notification_sorted_by_nearest_date(): void
    {
        $agreement = $this->createAgreement([
            'end_date' => '2026-07-28',
            'termination_notice_date' => '2026-07-23',
            'status_id' => $this->activeStatus->id,
        ]);

        $notifications = $this->agreementNotifications();
        $matching = $notifications->where('id', 'agreement_upcoming_'.$agreement->id);

        $this->assertCount(1, $matching);

        $notification = $matching->first();
        $this->assertSame('agreement_termination_notice', $notification['type']);
        $this->assertStringContainsString('Termination notice in 3 days', $notification['simple_message']);
        $this->assertStringContainsString('agreement ends in 8 days', $notification['simple_message']);
        $this->assertSame(Carbon::parse('2026-07-23')->startOfDay()->timestamp, $notification['sort_key']);
    }

    public function test_agreement_with_dates_outside_ten_day_window_is_excluded(): void
    {
        $agreement = $this->createAgreement([
            'end_date' => '2026-07-31',
            'termination_notice_date' => '2026-08-01',
            'status_id' => $this->activeStatus->id,
        ]);

        $this->assertNull($this->agreementNotificationFor($agreement->id));
    }

    public function test_swap_status_is_included_and_terminated_is_excluded(): void
    {
        $swapAgreement = $this->createAgreement([
            'end_date' => '2026-07-24',
            'status_id' => $this->swapStatus->id,
            'car_id' => $this->createSecondCar('NTF002')->id,
        ]);

        $terminatedAgreement = $this->createAgreement([
            'end_date' => '2026-07-24',
            'status_id' => $this->terminatedStatus->id,
            'car_id' => $this->createSecondCar('NTF003')->id,
        ]);

        $this->assertNotNull($this->agreementNotificationFor($swapAgreement->id));
        $this->assertNull($this->agreementNotificationFor($terminatedAgreement->id));
    }

    public function test_fleet_notifications_json_returns_agreement_alerts_in_ascending_sort_key_order(): void
    {
        $earlierAgreement = $this->createAgreement([
            'end_date' => '2026-07-22',
            'status_id' => $this->activeStatus->id,
            'car_id' => $this->createSecondCar('NTF010')->id,
        ]);

        $laterAgreement = $this->createAgreement([
            'end_date' => '2026-07-29',
            'termination_notice_date' => '2026-07-27',
            'status_id' => $this->activeStatus->id,
            'car_id' => $this->createSecondCar('NTF011')->id,
        ]);

        $response = $this->getJson(route('notifications.index', ['type' => 'agreement_notifications']), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();

        $rows = collect($response->json('data'));
        $sortKeys = $rows->pluck('sort_key')->values()->all();

        $this->assertSame([
            Carbon::parse('2026-07-22')->startOfDay()->timestamp,
            Carbon::parse('2026-07-27')->startOfDay()->timestamp,
        ], $sortKeys);

        $this->assertSame('agreement_upcoming_'.$earlierAgreement->id, $rows->first()['id']);
        $this->assertSame('agreement_upcoming_'.$laterAgreement->id, $rows->last()['id']);
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
            'end_date' => '2027-07-20',
            'agreed_rent' => 200,
            'rent_interval' => 'weekly',
            'deposit_amount' => 500,
            'collection_type' => 'weekly',
            'using_own_insurance' => false,
            'status_id' => $this->activeStatus->id,
            'createdBy' => $this->user->id,
            'updatedBy' => $this->user->id,
        ], $overrides));
    }

    private function createSecondCar(string $registration): Car
    {
        $carModelId = DB::table('car_models')->where('tenant_id', $this->tenant->id)->value('id');
        $counselId = DB::table('counsels')->value('id');

        return $this->createCompliantCar($registration, (int) $carModelId, (int) $counselId, 'rented');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function agreementNotificationFor(int $agreementId): ?array
    {
        return $this->agreementNotifications()
            ->firstWhere('id', 'agreement_upcoming_'.$agreementId);
    }

    private function agreementNotifications()
    {
        $response = $this->getJson(route('dashboard.fleet-notifications'));

        $response->assertOk();

        return collect($response->json('notifications'))
            ->filter(fn (array $notification) => in_array($notification['type'], ['agreement_end_date', 'agreement_termination_notice'], true));
    }

    private function setUpHttpTestExtras(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->default(1);
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
            $table->date('driver_license_expiry_date')->nullable();
            $table->date('phd_license_expiry_date')->nullable();
        });

        Schema::table('agreements', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_agreement_id')->nullable();
        });

        Schema::table('car_insurances', function (Blueprint $table) {
            $table->foreignId('status_id')->nullable();
            $table->date('applied_date')->nullable();
            $table->date('start_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->integer('notify_before_expiry')->default(30);
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
    }

    private function createCompliantCar(string $registration, int $carModelId, int $counselId, string $fleetStatus): Car
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
            'fleet_status' => $fleetStatus,
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
            'expiry_date' => '2027-01-01',
            'notify_before_expiry' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $car;
    }
}
