<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Car;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Status;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AgreementUpgradeService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\Concerns\SetupAgreementChangeCarDatabase;
use Tests\TestCase;

class VehicleSwapAgreementTest extends TestCase
{
    use SetupAgreementChangeCarDatabase;

    private Tenant $tenant;

    private Company $company;

    private Driver $driver;

    private Car $oldCar;

    private Car $newCar;

    private Status $activeStatus;

    private Status $swapStatus;

    private Status $terminatedStatus;

    private Agreement $agreement;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpAgreementChangeCarDatabase();
        $this->setUpHttpTestExtras();

        Carbon::setTestNow(Carbon::parse('2026-06-18 10:00:00'));

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Swap Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $this->company = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Swap Company',
        ]);
        $this->driver = Driver::query()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Swap',
            'last_name' => 'Driver',
            'email' => 'swap@example.com',
            'phone_number' => '07000000002',
        ]);

        $carModelId = DB::table('car_models')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'name' => 'Swap Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $counselId = DB::table('counsels')->insertGetId([
            'name' => 'Swap Counsel',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->oldCar = $this->createCompliantCar('SWPOLD', $carModelId, $counselId, 'rented');
        $this->newCar = $this->createCompliantCar('SWPNEW', $carModelId, $counselId, 'available_for_rent');

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

        $this->agreement = Agreement::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $this->oldCar->id,
            'start_date' => '2026-06-01',
            'end_date' => '2027-06-01',
            'agreed_rent' => 200,
            'rent_interval' => 'weekly',
            'deposit_amount' => 500,
            'collection_type' => 'weekly',
            'using_own_insurance' => false,
            'status_id' => $this->activeStatus->id,
            'createdBy' => $this->user->id,
            'updatedBy' => $this->user->id,
        ]);
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

    public function test_vehicle_swaps_routes_are_removed(): void
    {
        $this->get('/admin/vehicle-swaps')->assertNotFound();
        $this->get('/admin/vehicle-swaps/create')->assertNotFound();
        $this->post('/admin/vehicle-swaps', [])->assertNotFound();
    }

    public function test_change_car_routes_are_removed(): void
    {
        $this->get(route('agreements.show', $this->agreement).'/upgrade-cars')->assertNotFound();
        $this->post(route('agreements.show', $this->agreement).'/upgrade-car', [])->assertNotFound();
    }

    public function test_swap_service_terminates_old_agreement_and_creates_linked_swap_agreement(): void
    {
        $newAgreement = app(AgreementUpgradeService::class)->createSwapFromAgreement($this->agreement, [
            'car_id' => $this->newCar->id,
            'driver_id' => $this->driver->id,
            'agreed_rent' => 250,
        ]);

        $oldAgreement = $this->agreement->fresh(['status']);
        $this->assertSame('Terminated', $oldAgreement->status->name);
        $this->assertSame($this->newCar->id, $newAgreement->car_id);
        $this->assertSame('250.00', $newAgreement->agreed_rent);
        $this->assertSame('Swap', $newAgreement->fresh('status')->status->name);
        $this->assertSame($this->agreement->id, $newAgreement->upgraded_from_agreement_id);
        $this->assertSame('500.00', $newAgreement->deposit_amount);
    }

    public function test_permission_letter_route_returns_success_for_swapped_agreement(): void
    {
        $newAgreement = app(AgreementUpgradeService::class)->createSwapFromAgreement($this->agreement, [
            'car_id' => $this->newCar->id,
            'driver_id' => $this->driver->id,
            'agreed_rent' => 250,
        ]);

        $response = $this->get(route('agreements.permission-letter', $newAgreement));

        $response->assertOk();
    }

    public function test_swap_uses_new_car_company_for_agreement_and_documents(): void
    {
        $oldCompany = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Samore Traders Ltd',
        ]);
        $newCompany = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Proactive Hybrid Corporate Ltd',
        ]);

        $this->oldCar->update(['company_id' => $oldCompany->id]);
        $this->newCar->update(['company_id' => $newCompany->id]);
        $this->agreement->update(['company_id' => $oldCompany->id]);

        $newAgreement = app(AgreementUpgradeService::class)->createSwapFromAgreement($this->agreement->fresh(), [
            'car_id' => $this->newCar->id,
            'driver_id' => $this->driver->id,
            'agreed_rent' => 250,
        ]);

        $newAgreement->load(['car.company', 'company']);

        $this->assertSame($newCompany->id, $newAgreement->company_id);
        $this->assertSame($newCompany->id, $newAgreement->documentCompany()?->id);

        $letterMeta = app(\App\Services\PermissionLetterService::class)
            ->resolveLetterMeta($newAgreement->documentCompany());

        $this->assertSame('PROACTIVE HYBRID CORPORATE LTD', $letterMeta['owned_by_name']);

        $this->get(route('agreements.permission-letter', $newAgreement))->assertOk();
    }

    public function test_document_company_uses_car_company_when_stored_agreement_company_differs(): void
    {
        $oldCompany = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Samore Traders Ltd',
        ]);
        $newCompany = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Proactive Hybrid Corporate Ltd',
        ]);

        $this->newCar->update(['company_id' => $newCompany->id]);

        $swappedAgreement = Agreement::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $oldCompany->id,
            'driver_id' => $this->driver->id,
            'car_id' => $this->newCar->id,
            'start_date' => '2026-06-18 10:00:00',
            'end_date' => '2027-06-01',
            'agreed_rent' => 250,
            'rent_interval' => 'weekly',
            'deposit_amount' => 500,
            'collection_type' => 'weekly',
            'using_own_insurance' => false,
            'status_id' => $this->swapStatus->id,
            'upgraded_from_agreement_id' => $this->agreement->id,
            'createdBy' => $this->user->id,
            'updatedBy' => $this->user->id,
        ]);

        $swappedAgreement->load(['car.company', 'company']);

        $this->assertSame($oldCompany->id, $swappedAgreement->company_id);
        $this->assertSame($newCompany->id, $swappedAgreement->documentCompany()?->id);
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

        return $car->fresh(['mots', 'roadTaxes', 'phvs', 'reservations']);
    }
}
