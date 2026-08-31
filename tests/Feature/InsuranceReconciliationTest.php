<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarInsurance;
use App\Models\Company;
use App\Models\Status;
use App\Models\Tenant;
use App\Services\FleetPolicyScheduleParser;
use App\Services\InsuranceReconciliationService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InsuranceReconciliationTest extends TestCase
{
    private Tenant $tenant;

    private Company $company;

    private int $carModelId;

    private int $providerId;

    private Status $activeStatus;

    private InsuranceReconciliationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
        Carbon::setTestNow(Carbon::parse('2026-07-01 12:00:00'));

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Reconcile Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $this->company = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Reconcile Co',
        ]);
        $this->carModelId = (int) DB::table('car_models')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'name' => 'Prius',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->activeStatus = Status::query()->create(['name' => 'Active', 'type' => 'insurance']);
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

        $this->service = app(InsuranceReconciliationService::class);
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

    public function test_reconcile_classifies_matched_pdf_only_system_only_and_duplicates(): void
    {
        $matchedCar = $this->createCar('BR68VXW');
        $systemOnlyCar = $this->createCar('SY11TEM');
        $duplicateSystemCar = $this->createCar('DU11SYS');
        $this->createCar('FL11EET');

        $this->createPolicy($matchedCar, [
            'start_date' => '2026-06-01',
            'expiry_date' => '2026-08-01',
        ]);
        $this->createPolicy($systemOnlyCar, [
            'start_date' => '2026-06-01',
            'expiry_date' => '2026-08-01',
        ]);
        $this->createPolicy($duplicateSystemCar, [
            'start_date' => '2026-06-01',
            'expiry_date' => '2026-08-01',
        ]);
        $this->createPolicy($duplicateSystemCar, [
            'start_date' => '2026-06-15',
            'expiry_date' => '2026-07-31',
        ]);

        $line = static fn (string $reg): string => "24/06/2026 TOYOTA PRIUS\t0T\t{$reg}\tComprehensive\t£295";
        $parsed = (new FleetPolicyScheduleParser)->parseText(implode("\n", [
            'Policy NumberMTFLTEST',
            'Inception24/06/2026',
            'Expiry21/07/2026',
            $line('BR68VXW'),
            $line('PD99AAA'),
            $line('DU11PDF'),
            $line('DU11PDF'),
        ]));

        $result = $this->service->reconcileParsed(
            $this->tenant->id,
            Carbon::parse('2026-06-24'),
            Carbon::parse('2026-07-21'),
            $parsed
        );

        $this->assertSame(1, $result['summary']['matched']);
        $this->assertSame(2, $result['summary']['pdf_only']);
        $this->assertSame(2, $result['summary']['system_only']);
        $this->assertSame(1, $result['summary']['pdf_duplicates']);
        $this->assertSame(1, $result['summary']['system_duplicates']);

        $this->assertSame(['BR68VXW'], collect($result['matched'])->pluck('registration')->all());
        $this->assertEqualsCanonicalizing(['PD99AAA', 'DU11PDF'], collect($result['pdf_only'])->pluck('registration')->all());
        $this->assertEqualsCanonicalizing(['SY11TEM', 'DU11SYS'], collect($result['system_only'])->pluck('registration')->all());
        $this->assertSame(['DU11PDF'], collect($result['pdf_duplicates'])->pluck('registration')->all());
        $this->assertSame(['DU11SYS'], collect($result['system_duplicates'])->pluck('registration')->all());
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
            'start_date' => '2026-06-01',
            'expiry_date' => '2026-08-01',
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
            $table->foreignId('tenant_id');
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

        Schema::create('insurance_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('company_id')->nullable();
            $table->string('provider_name');
            $table->string('email')->nullable();
            $table->string('insurance_type')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('policy_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->foreignId('status_id')->nullable();
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
            $table->year('registration_year')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->string('purchase_type')->default('uk');
            $table->string('fleet_status')->default('available_for_rent');
            $table->boolean('sorn_applied')->default(false);
            $table->timestamps();
        });

        Schema::create('car_insurances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('car_id');
            $table->foreignId('insurance_provider_id')->nullable();
            $table->foreignId('status_id')->nullable();
            $table->date('start_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('applied_date')->nullable();
            $table->date('canceled_date')->nullable();
            $table->integer('notify_before_expiry')->default(30);
            $table->timestamps();
        });
    }
}
