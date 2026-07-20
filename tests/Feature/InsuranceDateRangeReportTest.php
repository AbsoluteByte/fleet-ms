<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarInsurance;
use App\Models\Company;
use App\Models\Status;
use App\Models\Tenant;
use App\Services\InsuranceDateRangeReportService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InsuranceDateRangeReportTest extends TestCase
{
    private Tenant $tenant;

    private Company $company;

    private int $carModelId;

    private int $providerId;

    private Status $activeStatus;

    private Status $cancelledStatus;

    private InsuranceDateRangeReportService $service;

    private Carbon $from;

    private Carbon $to;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
        Carbon::setTestNow(Carbon::parse('2026-07-20 12:00:00'));

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Insurance Report Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $this->company = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Insurance Report Company',
        ]);
        $this->carModelId = (int) DB::table('car_models')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'name' => 'Report Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->activeStatus = Status::query()->create(['name' => 'Active', 'type' => 'insurance']);
        $this->cancelledStatus = Status::query()->create(['name' => 'Cancelled', 'type' => 'insurance']);
        $this->providerId = (int) DB::table('insurance_providers')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'provider_name' => 'Test Provider',
            'email' => 'provider@example.com',
            'insurance_type' => 'Fleet',
            'amount' => 1000,
            'policy_number' => 'POL-1',
            'expiry_date' => now()->addYear()->toDateString(),
            'status_id' => $this->activeStatus->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->service = app(InsuranceDateRangeReportService::class);
        $this->from = Carbon::parse('2026-07-01')->startOfDay();
        $this->to = Carbon::parse('2026-07-31')->startOfDay();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Schema::dropIfExists('car_insurances');
        Schema::dropIfExists('insurance_providers');
        Schema::dropIfExists('cars');
        Schema::dropIfExists('car_models');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('statuses');
        Schema::dropIfExists('tenants');

        parent::tearDown();
    }

    public function test_removed_in_range_includes_only_cancellations_inside_range(): void
    {
        $car = $this->createCar('REM001');
        $inRange = $this->createPolicy($car, [
            'start_date' => '2026-06-01',
            'canceled_date' => '2026-07-15',
            'status_id' => $this->cancelledStatus->id,
        ]);
        $this->createPolicy($car, [
            'start_date' => '2026-05-01',
            'canceled_date' => '2026-06-15',
            'status_id' => $this->cancelledStatus->id,
            'expiry_date' => '2026-12-01',
        ]);

        $rows = $this->service->removedInRange($this->tenant->id, $this->from, $this->to);

        $this->assertCount(1, $rows);
        $this->assertSame($inRange->id, $rows->first()->policy_id);
    }

    public function test_activated_in_range_includes_started_policies_even_if_later_cancelled(): void
    {
        $car = $this->createCar('ACT001');
        $startedAndCancelled = $this->createPolicy($car, [
            'start_date' => '2026-07-10',
            'canceled_date' => '2026-07-20',
            'status_id' => $this->cancelledStatus->id,
        ]);
        $this->createPolicy($car, [
            'start_date' => '2026-06-10',
            'canceled_date' => null,
            'status_id' => $this->activeStatus->id,
        ]);

        $rows = $this->service->activatedInRange($this->tenant->id, $this->from, $this->to);

        $this->assertCount(1, $rows);
        $this->assertSame($startedAndCancelled->id, $rows->first()->policy_id);
    }

    public function test_activated_or_removed_in_range_is_full_merge_allowing_duplicates(): void
    {
        $carA = $this->createCar('UNI001');
        $carB = $this->createCar('UNI002');

        $removedOnly = $this->createPolicy($carA, [
            'start_date' => '2026-05-01',
            'canceled_date' => '2026-07-12',
            'status_id' => $this->cancelledStatus->id,
            'expiry_date' => '2026-12-01',
        ]);
        $activatedOnly = $this->createPolicy($carB, [
            'start_date' => '2026-07-05',
            'canceled_date' => null,
            'status_id' => $this->activeStatus->id,
        ]);
        $both = $this->createPolicy($carA, [
            'start_date' => '2026-07-08',
            'canceled_date' => '2026-07-18',
            'status_id' => $this->cancelledStatus->id,
            'expiry_date' => '2027-01-01',
        ]);

        $rows = $this->service->activatedOrRemovedInRange($this->tenant->id, $this->from, $this->to);
        $ids = $rows->pluck('policy_id')->all();

        $this->assertCount(4, $rows);
        $this->assertSame(1, collect($ids)->filter(fn ($id) => $id === $removedOnly->id)->count());
        $this->assertSame(1, collect($ids)->filter(fn ($id) => $id === $activatedOnly->id)->count());
        $this->assertSame(2, collect($ids)->filter(fn ($id) => $id === $both->id)->count());
    }

    public function test_active_on_insurance_ignores_date_range_and_shows_currently_active_only(): void
    {
        $car = $this->createCar('CUR001');
        $this->createPolicy($car, [
            'start_date' => '2026-01-01',
            'canceled_date' => '2026-06-01',
            'status_id' => $this->cancelledStatus->id,
            'expiry_date' => '2026-12-01',
        ]);
        $current = $this->createPolicy($car, [
            'start_date' => '2026-02-01',
            'canceled_date' => null,
            'status_id' => $this->activeStatus->id,
            'expiry_date' => '2027-12-01',
        ]);

        $rows = $this->service->activeOnInsurance($this->tenant->id);

        $this->assertCount(1, $rows);
        $this->assertSame($current->id, $rows->first()->policy_id);
    }

    private function createCar(string $registration): Car
    {
        return Car::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'car_model_id' => $this->carModelId,
            'registration' => $registration,
            'color' => 'Black',
            'vin' => 'VIN'.$registration,
            'manufacture_year' => 2020,
            'purchase_date' => '2020-01-01',
            'purchase_price' => 10000,
            'purchase_type' => 'uk',
            'fleet_status' => Car::FLEET_STATUS_AVAILABLE_FOR_RENT,
        ]);
    }

    private function createPolicy(Car $car, array $overrides = []): CarInsurance
    {
        return CarInsurance::query()->create(array_merge([
            'tenant_id' => $this->tenant->id,
            'car_id' => $car->id,
            'insurance_provider_id' => $this->providerId,
            'start_date' => '2026-07-01',
            'expiry_date' => '2027-07-01',
            'canceled_date' => null,
            'notify_before_expiry' => 30,
            'status_id' => $this->activeStatus->id,
        ], $overrides));
    }

    private function setUpDatabase(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('car_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->timestamps();
        });

        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('company_id');
            $table->foreignId('car_model_id');
            $table->string('registration')->unique();
            $table->string('color')->nullable();
            $table->string('vin')->nullable();
            $table->year('manufacture_year')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->string('purchase_type')->default('uk');
            $table->string('fleet_status')->default('available_for_rent');
            $table->boolean('sorn_applied')->default(false);
            $table->timestamps();
        });

        Schema::create('insurance_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('company_id')->nullable();
            $table->string('provider_name');
            $table->string('email')->nullable();
            $table->string('insurance_type')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('policy_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->foreignId('status_id')->nullable();
            $table->timestamps();
        });

        Schema::create('car_insurances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('car_id');
            $table->foreignId('insurance_provider_id');
            $table->date('start_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('applied_date')->nullable();
            $table->date('canceled_date')->nullable();
            $table->string('insurance_document')->nullable();
            $table->integer('notify_before_expiry')->nullable();
            $table->foreignId('status_id');
            $table->timestamps();
        });
    }
}
