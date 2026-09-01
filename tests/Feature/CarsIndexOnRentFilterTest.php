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

class CarsIndexOnRentFilterTest extends TestCase
{
    use SetupAgreementChangeCarDatabase;

    private Tenant $tenant;

    private Company $company;

    private User $user;

    private Driver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpAgreementChangeCarDatabase();
        $this->setUpHttpTestExtras();

        $this->tenant = Tenant::query()->create([
            'company_name' => 'On Rent Filter Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $this->company = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Fleet Co',
        ]);
        $this->driver = Driver::query()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Test',
            'last_name' => 'Driver',
            'email' => 'on-rent-filter@example.com',
            'is_active' => true,
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
        Schema::dropIfExists('car_status_histories');
        Schema::dropIfExists('car_services');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('tenant_user');
        $this->tearDownAgreementChangeCarDatabase();

        parent::tearDown();
    }

    public function test_cars_index_marks_cars_with_active_or_swap_agreements_for_on_rent_filter(): void
    {
        $activeStatus = Status::query()->create(['name' => 'Active', 'type' => 'agreement']);
        $swapStatus = Status::query()->create(['name' => 'Swap', 'type' => 'agreement']);
        $terminatedStatus = Status::query()->create(['name' => 'Terminated', 'type' => 'agreement']);

        $activeCar = $this->createCar('ACTV001', Car::FLEET_STATUS_AVAILABLE_FOR_RENT);
        $swapCar = $this->createCar('SWAP001', Car::FLEET_STATUS_AVAILABLE_FOR_RENT);
        $fleetOnlyCar = $this->createCar('FLTONLY', Car::FLEET_STATUS_ON_RENT);
        $terminatedCar = $this->createCar('TERM001', Car::FLEET_STATUS_ON_RENT);
        $availableCar = $this->createCar('AVAIL01', Car::FLEET_STATUS_AVAILABLE_FOR_RENT);

        $this->createAgreement($activeCar, $activeStatus);
        $this->createAgreement($swapCar, $swapStatus);
        $this->createAgreement($terminatedCar, $terminatedStatus);

        $activeOrSwapCarIds = Agreement::activeOrSwapCarIdsForTenant($this->tenant->id);
        $this->assertSame([$activeCar->id, $swapCar->id], $activeOrSwapCarIds);

        $response = $this->get(route('cars.index'));

        $response->assertOk();
        $this->assertStringContainsString(
            'data-has-active-or-swap-agreement="1"',
            $this->rowSnippetForRegistration($response->getContent(), 'ACTV001')
        );
        $this->assertStringContainsString(
            'data-has-active-or-swap-agreement="1"',
            $this->rowSnippetForRegistration($response->getContent(), 'SWAP001')
        );
        $this->assertStringContainsString(
            'data-has-active-or-swap-agreement="0"',
            $this->rowSnippetForRegistration($response->getContent(), 'FLTONLY')
        );
        $this->assertStringContainsString(
            'data-has-active-or-swap-agreement="0"',
            $this->rowSnippetForRegistration($response->getContent(), 'TERM001')
        );
        $this->assertStringContainsString(
            'data-has-active-or-swap-agreement="0"',
            $this->rowSnippetForRegistration($response->getContent(), 'AVAIL01')
        );
    }

    private function createCar(string $registration, string $fleetStatus): Car
    {
        $carModelId = (int) DB::table('car_models')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Car::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'car_model_id' => $carModelId,
            'registration' => $registration,
            'color' => 'Black',
            'vin' => 'VIN'.$registration,
            'manufacture_year' => 2020,
            'registration_year' => 2020,
            'purchase_date' => '2020-01-01',
            'purchase_price' => 10000,
            'purchase_type' => 'uk',
            'fleet_status' => $fleetStatus,
        ]);
    }

    private function createAgreement(Car $car, Status $status): Agreement
    {
        return Agreement::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $car->id,
            'status_id' => $status->id,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'agreed_rent' => 200,
            'rent_interval' => 'weekly',
        ]);
    }

    private function rowSnippetForRegistration(string $html, string $registration): string
    {
        if (! preg_match('/<tr[^>]*>.*?'.preg_quote($registration, '/').'.*?<\/tr>/s', $html, $matches)) {
            $this->fail('Could not find table row for registration '.$registration);
        }

        return $matches[0];
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

        Schema::create('car_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id');
            $table->timestamps();
        });
    }
}
