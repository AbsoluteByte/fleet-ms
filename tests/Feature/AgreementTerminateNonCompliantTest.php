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

class AgreementTerminateNonCompliantTest extends TestCase
{
    use SetupAgreementChangeCarDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpAgreementChangeCarDatabase();
        $this->setUpHttpTestExtras();

        Carbon::setTestNow(Carbon::parse('2026-06-20 10:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Schema::dropIfExists('agreement_collections');
        Schema::dropIfExists('car_status_histories');
        Schema::dropIfExists('tenant_user');
        $this->tearDownAgreementChangeCarDatabase();

        parent::tearDown();
    }

    public function test_can_terminate_agreement_when_car_is_non_compliant(): void
    {
        $tenant = Tenant::query()->create([
            'company_name' => 'Terminate Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $company = Company::query()->create(['tenant_id' => $tenant->id, 'name' => 'Co']);
        $driver = Driver::query()->create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'phone_number' => '07000000001',
        ]);

        $carModelId = DB::table('car_models')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => 'Test Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $counselId = DB::table('counsels')->insertGetId([
            'name' => 'Test Counsel',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $car = $this->createNonCompliantCar($tenant->id, $company->id, $carModelId, $counselId);
        $activeStatus = Status::query()->create(['name' => 'Active', 'type' => 'agreement']);

        $user = User::factory()->create();
        $user->tenants()->attach($tenant->id, [
            'role' => 'admin',
            'is_primary' => true,
            'joined_at' => now(),
        ]);
        $this->actingAs($user);
        $user->switchTenant($tenant->id);

        $agreement = Agreement::query()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'car_id' => $car->id,
            'start_date' => '2026-06-01',
            'end_date' => '2027-06-01',
            'agreed_rent' => 150,
            'rent_interval' => 'Weekly',
            'deposit_amount' => 200,
            'collection_type' => 'weekly',
            'status_id' => $activeStatus->id,
        ]);

        $response = $this->from(route('agreements.edit', $agreement))
            ->put(route('agreements.update', $agreement), [
                'company_id' => $company->id,
                'driver_id' => $driver->id,
                'car_id' => $car->id,
                'start_date' => '2026-06-01T09:00',
                'end_date' => '2027-06-01',
                'agreed_rent' => 150,
                'rent_interval' => 'Weekly',
                'collection_type' => 'weekly',
                'deposit_amount' => 200,
                'status_id' => $activeStatus->id,
                'termination_notice_date' => '2026-06-20',
                'termination_available_from_date' => '2026-06-25',
                'termination_notes' => 'Vehicle non-compliant',
            ]);

        $response->assertRedirect(route('agreements.index'));
        $response->assertSessionHasNoErrors();

        $agreement->refresh();
        $car->refresh();

        $this->assertSame('2026-06-20', $agreement->termination_notice_date->toDateString());
        $this->assertSame(Car::FLEET_STATUS_NON_COMPLIANT, $car->fleet_status);
    }

    public function test_can_terminate_agreement_via_status_when_car_is_non_compliant(): void
    {
        $tenant = Tenant::query()->create([
            'company_name' => 'Terminate Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $company = Company::query()->create(['tenant_id' => $tenant->id, 'name' => 'Co']);
        $driver = Driver::query()->create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'phone_number' => '07000000001',
        ]);

        $carModelId = DB::table('car_models')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => 'Test Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $counselId = DB::table('counsels')->insertGetId([
            'name' => 'Test Counsel',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $car = $this->createNonCompliantCar($tenant->id, $company->id, $carModelId, $counselId);
        $activeStatus = Status::query()->create(['name' => 'Active', 'type' => 'agreement']);
        $terminatedStatus = Status::query()->create(['name' => 'Terminated', 'type' => 'agreement']);

        $user = User::factory()->create();
        $user->tenants()->attach($tenant->id, [
            'role' => 'admin',
            'is_primary' => true,
            'joined_at' => now(),
        ]);
        $this->actingAs($user);
        $user->switchTenant($tenant->id);

        $agreement = Agreement::query()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'car_id' => $car->id,
            'start_date' => '2026-06-01',
            'end_date' => '2027-06-01',
            'agreed_rent' => 150,
            'rent_interval' => 'Weekly',
            'deposit_amount' => 200,
            'collection_type' => 'weekly',
            'status_id' => $activeStatus->id,
        ]);

        $response = $this->from(route('agreements.edit', $agreement))
            ->put(route('agreements.update', $agreement), [
                'company_id' => $company->id,
                'driver_id' => $driver->id,
                'car_id' => $car->id,
                'start_date' => '2026-06-01T09:00',
                'end_date' => '2027-06-01',
                'agreed_rent' => 150,
                'rent_interval' => 'Weekly',
                'collection_type' => 'weekly',
                'deposit_amount' => 200,
                'status_id' => $terminatedStatus->id,
                'closing_date' => '2026-06-20T14:30',
            ]);

        $response->assertRedirect(route('agreements.index'));
        $response->assertSessionHasNoErrors();

        $agreement->refresh();
        $car->refresh();

        $this->assertSame($terminatedStatus->id, $agreement->status_id);
        $this->assertSame(Car::FLEET_STATUS_NON_COMPLIANT, $car->fleet_status);
    }

    private function setUpHttpTestExtras(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->default(1);
        });

        Schema::table('agreements', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_agreement_id')->nullable();
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
    }

    private function createNonCompliantCar(int $tenantId, int $companyId, int $carModelId, int $counselId): Car
    {
        $car = Car::query()->create([
            'tenant_id' => $tenantId,
            'company_id' => $companyId,
            'car_model_id' => $carModelId,
            'registration' => 'NC123',
            'color' => 'Black',
            'vin' => 'VINNC123',
            'manufacture_year' => 2020,
            'registration_year' => 2020,
            'purchase_date' => '2020-01-01',
            'purchase_price' => 10000,
            'purchase_type' => 'uk',
            'fleet_status' => Car::FLEET_STATUS_NON_COMPLIANT,
            'sorn_applied' => false,
        ]);

        DB::table('car_mots')->insert([
            'car_id' => $car->id,
            'expiry_date' => '2026-01-01',
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
            'amount' => 100,
            'start_date' => '2026-01-01',
            'expiry_date' => '2027-01-01',
            'notify_before_expiry' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $car->fresh(['mots', 'roadTaxes', 'phvs']);
    }
}
