<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\AgreementAdditionalCharge;
use App\Models\Car;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Invoice;
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

class AgreementAdditionalChargeTest extends TestCase
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

        Carbon::setTestNow(Carbon::parse('2026-06-20 10:00:00'));

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Additional Charge Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $this->company = Company::query()->create(['tenant_id' => $this->tenant->id, 'name' => 'Co']);
        $this->driver = Driver::query()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Alex',
            'last_name' => 'Driver',
            'email' => 'alex-charge@example.com',
            'phone_number' => '07000000005',
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

        $this->car = $this->createCompliantCar($this->tenant->id, $this->company->id, $carModelId, $counselId);
        $this->activeStatus = Status::query()->create(['name' => 'Active', 'type' => 'agreement']);
        $this->expiredStatus = Status::query()->create(['name' => 'Expired', 'type' => 'agreement']);

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
        Schema::dropIfExists('agreement_additional_charges');
        Schema::dropIfExists('agreement_collections');
        Schema::dropIfExists('car_status_histories');
        Schema::dropIfExists('tenant_user');
        $this->tearDownAgreementChangeCarDatabase();

        parent::tearDown();
    }

    public function test_new_charge_on_update_creates_one_invoice(): void
    {
        $agreement = $this->createActiveAgreement();

        $response = $this->put(route('agreements.update', $agreement), $this->basePayload([
            'additional_charges_present' => 1,
            'additional_charges' => [
                $this->damageRow([
                    'type' => AgreementAdditionalCharge::TYPE_INSURANCE_EXCESS,
                    'amount' => 85.50,
                    'notes' => 'Tyre replacement',
                ]),
            ],
        ]));

        $response->assertRedirect(route('agreements.index'));
        $response->assertSessionHasNoErrors();

        $charge = AgreementAdditionalCharge::query()->where('agreement_id', $agreement->id)->first();
        $this->assertNotNull($charge);
        $this->assertSame(AgreementAdditionalCharge::TYPE_INSURANCE_EXCESS, $charge->type);
        $this->assertSame('85.50', number_format((float) $charge->amount, 2, '.', ''));
        $this->assertSame('Tyre replacement', $charge->notes);
        $this->assertNotNull($charge->invoice_id);

        $invoice = Invoice::query()->findOrFail($charge->invoice_id);
        $this->assertSame('agreement_additional_charge', $invoice->invoice_type);
        $this->assertSame($agreement->id, $invoice->source_id);
        $this->assertSame('85.50', number_format((float) $invoice->total_amount, 2, '.', ''));
        $this->assertSame('Insurance Excess: Tyre replacement', $invoice->notes);
        $this->assertSame(1, Invoice::query()->where('invoice_type', 'agreement_additional_charge')->count());
    }

    public function test_resave_without_new_rows_does_not_create_duplicate_invoices(): void
    {
        $agreement = $this->createActiveAgreement();

        $this->put(route('agreements.update', $agreement), $this->basePayload([
            'additional_charges_present' => 1,
            'additional_charges' => [
                $this->damageRow(['amount' => 50, 'notes' => 'Bulb replacement']),
            ],
        ]))->assertSessionHasNoErrors();

        $charge = AgreementAdditionalCharge::query()->where('agreement_id', $agreement->id)->firstOrFail();

        $this->put(route('agreements.update', $agreement), $this->basePayload([
            'additional_charges_present' => 1,
            'additional_charges' => [
                $this->damageRow([
                    'id' => $charge->id,
                    'type' => $charge->type,
                    'amount' => 50,
                    'notes' => 'Bulb replacement',
                ]),
            ],
        ]))->assertSessionHasNoErrors();

        $this->assertSame(1, AgreementAdditionalCharge::query()->where('agreement_id', $agreement->id)->count());
        $this->assertSame(1, Invoice::query()->where('invoice_type', 'agreement_additional_charge')->count());
    }

    public function test_modify_locked_charge_amount_is_rejected(): void
    {
        $agreement = $this->createActiveAgreement();

        $this->put(route('agreements.update', $agreement), $this->basePayload([
            'additional_charges_present' => 1,
            'additional_charges' => [
                $this->damageRow(['amount' => 40, 'notes' => 'Body repair']),
            ],
        ]))->assertSessionHasNoErrors();

        $charge = AgreementAdditionalCharge::query()->where('agreement_id', $agreement->id)->firstOrFail();

        $response = $this->from(route('agreements.edit', $agreement))
            ->put(route('agreements.update', $agreement), $this->basePayload([
                'additional_charges_present' => 1,
                'additional_charges' => [
                    $this->damageRow([
                        'id' => $charge->id,
                        'type' => $charge->type,
                        'amount' => 75,
                        'notes' => 'Body repair',
                    ]),
                ],
            ]));

        $response->assertSessionHasErrors('additional_charges');
    }

    public function test_remove_existing_charge_row_is_rejected(): void
    {
        $agreement = $this->createActiveAgreement();

        $this->put(route('agreements.update', $agreement), $this->basePayload([
            'additional_charges_present' => 1,
            'additional_charges' => [
                $this->damageRow([
                    'type' => AgreementAdditionalCharge::TYPE_INSURANCE_EXCESS,
                    'amount' => 30,
                    'notes' => 'Insurance excess',
                ]),
            ],
        ]))->assertSessionHasNoErrors();

        $response = $this->from(route('agreements.edit', $agreement))
            ->put(route('agreements.update', $agreement), $this->basePayload([
                'additional_charges_present' => 1,
                'additional_charges' => [],
            ]));

        $response->assertSessionHasErrors('additional_charges');
    }

    public function test_add_charge_outside_hire_period_is_rejected(): void
    {
        $agreement = $this->createActiveAgreement([
            'end_date' => '2026-06-01',
        ]);

        $response = $this->from(route('agreements.edit', $agreement))
            ->put(route('agreements.update', $agreement), $this->basePayload([
                'end_date' => '2026-06-01',
                'additional_charges_present' => 1,
                'additional_charges' => [
                    $this->damageRow(['amount' => 25, 'notes' => 'Late charge']),
                ],
            ]));

        $response->assertSessionHasErrors('additional_charges');
        $this->assertSame(0, AgreementAdditionalCharge::query()->count());
    }

    public function test_driver_outstanding_balance_increases_by_charge_amount(): void
    {
        $agreement = $this->createActiveAgreement();

        $this->put(route('agreements.update', $agreement), $this->basePayload([
            'additional_charges_present' => 1,
            'additional_charges' => [],
        ]))->assertSessionHasNoErrors();

        $before = (float) $this->driver->fresh()->total_due;

        $this->put(route('agreements.update', $agreement), $this->basePayload([
            'additional_charges_present' => 1,
            'additional_charges' => [
                $this->damageRow(['amount' => 120, 'notes' => 'Windscreen repair']),
            ],
        ]))->assertSessionHasNoErrors();

        $this->assertSame($before + 120, (float) $this->driver->fresh()->total_due);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function damageRow(array $overrides = []): array
    {
        return array_merge([
            'type' => AgreementAdditionalCharge::TYPE_MISCELLANEOUS_CHARGES,
            'amount' => 10,
            'notes' => 'Test damage',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function basePayload(array $overrides = []): array
    {
        return array_merge([
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $this->car->id,
            'start_date' => '2026-06-01T09:00',
            'end_date' => '2027-06-01',
            'agreed_rent' => 150,
            'rent_interval' => 'Weekly',
            'collection_type' => 'weekly',
            'deposit_amount' => 200,
            'status_id' => $this->activeStatus->id,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createActiveAgreement(array $overrides = []): Agreement
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

        Schema::table('drivers', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });

        Schema::table('agreements', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_agreement_id')->nullable();
            $table->text('mutual_detail_slip_document')->nullable();
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

        Schema::create('agreement_additional_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('agreement_id');
            $table->string('type', 50)->default('miscellaneous_charges');
            $table->decimal('amount', 12, 2);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable()->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    private function createCompliantCar(int $tenantId, int $companyId, int $carModelId, int $counselId): Car
    {
        $car = Car::query()->create([
            'tenant_id' => $tenantId,
            'company_id' => $companyId,
            'car_model_id' => $carModelId,
            'registration' => 'AC123',
            'color' => 'Blue',
            'vin' => 'VINAC123',
            'manufacture_year' => 2020,
            'registration_year' => 2020,
            'purchase_date' => '2020-01-01',
            'purchase_price' => 10000,
            'purchase_type' => 'uk',
            'fleet_status' => 'available_for_rent',
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
