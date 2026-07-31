<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Car;
use App\Models\CarReservation;
use App\Models\Company;
use App\Models\DailyFinancialSheet;
use App\Models\Driver;
use App\Models\Payment;
use App\Models\Status;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DailyFinancialSheetService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\Concerns\SetupAgreementChangeCarDatabase;
use Tests\TestCase;

class ReservationFinancialSheetTest extends TestCase
{
    use SetupAgreementChangeCarDatabase;

    private Tenant $tenant;

    private Company $company;

    private User $employee;

    private User $approver;

    private Driver $driver;

    private Car $car;

    private Status $activeStatus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpAgreementChangeCarDatabase();
        $this->setUpHttpTestExtras();

        Carbon::setTestNow(Carbon::parse('2026-06-25 10:00:00'));

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Reservation DFS Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $this->company = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Reservation DFS Co',
        ]);

        $carModelId = (int) DB::table('car_models')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->car = Car::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'car_model_id' => $carModelId,
            'registration' => 'RSV-DFS-1',
            'color' => 'Black',
            'vin' => 'VINRSVDFS1',
            'manufacture_year' => 2020,
            'registration_year' => 2020,
            'purchase_date' => '2020-01-01',
            'purchase_price' => 10000,
            'purchase_type' => 'uk',
            'fleet_status' => Car::FLEET_STATUS_AVAILABLE_FOR_RENT,
            'sorn_applied' => false,
        ]);

        $counselId = (int) DB::table('counsels')->insertGetId([
            'name' => 'Test Counsel',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('car_mots')->insert([
            'car_id' => $this->car->id,
            'expiry_date' => '2027-01-01',
            'amount' => 50,
            'term' => '12 months',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('car_road_taxes')->insert([
            'car_id' => $this->car->id,
            'start_date' => '2026-01-01',
            'term' => '12 months',
            'amount' => 200,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('car_phvs')->insert([
            'car_id' => $this->car->id,
            'counsel_id' => $counselId,
            'amount' => 100,
            'start_date' => '2026-01-01',
            'expiry_date' => '2027-01-01',
            'notify_before_expiry' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->driver = Driver::query()->create($this->agreementReadyDriverAttributes($this->tenant->id, [
            'first_name' => 'Reservation',
            'last_name' => 'Driver',
            'email' => 'reservation-dfs@example.com',
        ]));

        $this->activeStatus = Status::query()->create([
            'name' => 'Active',
            'type' => 'agreement',
        ]);

        $this->employee = User::factory()->create(['email' => 'reservation-dfs-employee@example.com']);
        $this->employee->tenants()->attach($this->tenant->id, [
            'role' => 'admin',
            'is_primary' => true,
            'joined_at' => now(),
        ]);

        $this->approver = User::factory()->create(['email' => 'jawad@samoretraders.com', 'name' => 'Jawad']);
        $this->approver->tenants()->attach($this->tenant->id, [
            'role' => 'admin',
            'is_primary' => true,
            'joined_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        Schema::dropIfExists('car_status_histories');
        Schema::dropIfExists('daily_financial_sheets');
        Schema::dropIfExists('financial_sheet_adjustments');
        Schema::dropIfExists('other_payments');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('driver_credit_transactions');
        Schema::dropIfExists('deposit_refunds');
        Schema::dropIfExists('agreement_collections');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        $this->tearDownAgreementChangeCarDatabase();

        parent::tearDown();
    }

    public function test_reservation_store_creates_pending_financial_sheet_entry(): void
    {
        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $response = $this->post(route('reservations.store'), [
            'driver_mode' => 'existing',
            'driver_id' => $this->driver->id,
            'car_id' => $this->car->id,
            'reservation_date' => '2026-06-25',
            'pick_up_date' => '2026-06-30',
            'agreed_rent' => 150,
            'agreed_advance' => 200,
            'amount_paid' => 75,
            'payment_method' => 'Cash',
        ]);

        $response->assertRedirect(route('reservations.index'));

        $reservation = CarReservation::query()->first();
        $this->assertNotNull($reservation);
        $this->assertSame(CarReservation::POSTING_STATUS_PENDING, $reservation->posting_status);

        $service = app(DailyFinancialSheetService::class);
        $entries = $service->entriesForDate($this->tenant->id, '2026-06-25');
        $entry = $entries->firstWhere('id', 'reservation-payment-'.$reservation->id);

        $this->assertNotNull($entry);
        $this->assertSame('Reservation payment', $entry['category']);
        $this->assertSame('in', $entry['direction']);
        $this->assertEquals(75.0, $entry['amount']);
        $this->assertSame('RSV-DFS-1', $entry['car_registration']);
    }

    public function test_approve_sheet_posts_reservation_payment(): void
    {
        $reservation = $this->createReservation([
            'posting_status' => CarReservation::POSTING_STATUS_PENDING,
        ]);

        $this->actingAs($this->approver);
        $this->approver->switchTenant($this->tenant->id);

        $service = app(DailyFinancialSheetService::class);
        $service->approveSheet($this->tenant->id, '2026-06-25', $this->approver->id);

        $this->assertSame(
            CarReservation::POSTING_STATUS_POSTED,
            $reservation->fresh()->posting_status
        );
        $this->assertDatabaseHas('daily_financial_sheets', [
            'tenant_id' => $this->tenant->id,
            'status' => DailyFinancialSheet::STATUS_APPROVED,
        ]);
    }

    public function test_reject_entries_cancels_pending_reservation_payment(): void
    {
        $reservation = $this->createReservation([
            'posting_status' => CarReservation::POSTING_STATUS_PENDING,
        ]);

        $this->actingAs($this->approver);
        $this->approver->switchTenant($this->tenant->id);

        $service = app(DailyFinancialSheetService::class);
        $service->rejectEntries(
            $this->tenant->id,
            '2026-06-25',
            $this->approver->id,
            ['reservation-payment-'.$reservation->id]
        );

        $this->assertSame(
            CarReservation::POSTING_STATUS_CANCELLED,
            $reservation->fresh()->posting_status
        );

        $pendingEntries = $service->entriesForDate($this->tenant->id, '2026-06-25')
            ->where('posting_status', Payment::POSTING_STATUS_PENDING);

        $this->assertFalse($pendingEntries->contains('id', 'reservation-payment-'.$reservation->id));
    }

    public function test_update_reservation_amount_while_pending_updates_financial_sheet_entry(): void
    {
        $reservation = $this->createReservation([
            'posting_status' => CarReservation::POSTING_STATUS_PENDING,
        ]);

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $response = $this->put(route('reservations.update', $reservation), [
            'driver_mode' => 'existing',
            'driver_id' => $this->driver->id,
            'car_id' => $this->car->id,
            'reservation_date' => '2026-06-25',
            'pick_up_date' => '2026-06-30',
            'agreed_rent' => 150,
            'agreed_advance' => 200,
            'amount_paid' => 100,
            'payment_method' => 'Cash',
        ]);

        $response->assertRedirect(route('reservations.index'));

        $service = app(DailyFinancialSheetService::class);
        $entry = $service->entriesForDate($this->tenant->id, '2026-06-25')
            ->firstWhere('id', 'reservation-payment-'.$reservation->id);

        $this->assertNotNull($entry);
        $this->assertEquals(100.0, $entry['amount']);
        $this->assertSame(CarReservation::POSTING_STATUS_PENDING, $reservation->fresh()->posting_status);
    }

    public function test_convert_posted_reservation_to_agreement_posts_payment_without_duplicate_dfs_entry(): void
    {
        $this->car->update(['fleet_status' => 'reserved']);

        $reservation = $this->createReservation([
            'posting_status' => CarReservation::POSTING_STATUS_POSTED,
        ]);

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $response = $this->from(route('agreements.create'))
            ->post(route('agreements.store'), [
            'reservation_id' => $reservation->id,
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $this->car->id,
            'start_date' => '2026-06-30T09:00',
            'end_date' => '2027-06-30',
            'agreed_rent' => 150,
            'rent_interval' => 'Weekly',
            'collection_type' => 'weekly',
            'deposit_amount' => 200,
            'status_id' => $this->activeStatus->id,
            'add_payment' => 1,
            'agreement_payments' => [
                [
                    'payment_method' => 'Cash',
                    'payment_date' => '2026-06-30',
                    'amount' => 75,
                    'notes' => 'Payment from reservation #'.$reservation->id,
                ],
            ],
        ]);

        $response->assertRedirect(route('agreements.index'));
        $response->assertSessionHasNoErrors();

        $agreement = Agreement::query()->first();
        $this->assertNotNull($agreement);

        $payment = Payment::query()->first();
        $this->assertNotNull($payment);
        $this->assertSame(Payment::POSTING_STATUS_POSTED, $payment->posting_status);

        $service = app(DailyFinancialSheetService::class);
        $agreementDateEntries = $service->entriesForDate($this->tenant->id, '2026-06-30')
            ->where('posting_status', Payment::POSTING_STATUS_PENDING);

        $this->assertFalse($agreementDateEntries->contains('id', 'payment-'.$payment->id));

        $reservationDateEntries = $service->entriesForDate($this->tenant->id, '2026-06-25');
        $this->assertTrue($reservationDateEntries->contains('id', 'reservation-payment-'.$reservation->id));

        $this->assertSame($agreement->id, CarReservation::withTrashed()->find($reservation->id)?->converted_agreement_id);
    }

    public function test_convert_pending_reservation_cancels_reservation_entry_and_creates_pending_agreement_payment(): void
    {
        $this->car->update(['fleet_status' => 'reserved']);

        $reservation = $this->createReservation([
            'posting_status' => CarReservation::POSTING_STATUS_PENDING,
        ]);

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $response = $this->from(route('agreements.create'))
            ->post(route('agreements.store'), [
            'reservation_id' => $reservation->id,
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $this->car->id,
            'start_date' => '2026-06-30T09:00',
            'end_date' => '2027-06-30',
            'agreed_rent' => 150,
            'rent_interval' => 'Weekly',
            'collection_type' => 'weekly',
            'deposit_amount' => 200,
            'status_id' => $this->activeStatus->id,
            'add_payment' => 1,
            'agreement_payments' => [
                [
                    'payment_method' => 'Cash',
                    'payment_date' => '2026-06-30',
                    'amount' => 75,
                    'notes' => 'Payment from reservation #'.$reservation->id,
                ],
            ],
        ]);

        $response->assertRedirect(route('agreements.index'));
        $response->assertSessionHasNoErrors();

        $this->assertSame(
            CarReservation::POSTING_STATUS_CANCELLED,
            CarReservation::withTrashed()->find($reservation->id)?->posting_status
        );

        $service = app(DailyFinancialSheetService::class);
        $reservationDateEntries = $service->entriesForDate($this->tenant->id, '2026-06-25')
            ->where('posting_status', Payment::POSTING_STATUS_PENDING);
        $this->assertFalse($reservationDateEntries->contains('id', 'reservation-payment-'.$reservation->id));

        $payment = Payment::query()->first();
        $this->assertNotNull($payment);
        $this->assertSame(Payment::POSTING_STATUS_PENDING, $payment->posting_status);

        $agreementDateEntries = $service->entriesForDate($this->tenant->id, '2026-06-30');
        $this->assertTrue($agreementDateEntries->contains('id', 'payment-'.$payment->id));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createReservation(array $overrides = []): CarReservation
    {
        return CarReservation::query()->create(array_merge([
            'tenant_id' => $this->tenant->id,
            'car_id' => $this->car->id,
            'driver_id' => $this->driver->id,
            'customer_name' => 'Reservation Driver',
            'reservation_date' => '2026-06-25',
            'pick_up_date' => '2026-06-30',
            'available_from_date' => '2026-06-30',
            'agreed_rent' => 150,
            'agreed_advance' => 200,
            'amount_paid' => 75,
            'payment_method' => 'Cash',
            'balance_payable_on_pickup' => 275,
            'status' => 'active',
            'created_by' => $this->employee->id,
        ], $overrides));
    }

    private function setUpHttpTestExtras(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->default(1);
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
            $table->date('dob')->nullable();
            $table->string('address1')->nullable();
            $table->string('post_code')->nullable();
            $table->string('town')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->string('driver_license_number')->nullable();
            $table->date('driver_license_expiry_date')->nullable();
            $table->string('next_of_kin')->nullable();
            $table->string('next_of_kin_phone')->nullable();
        });

        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('agreements', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_agreement_id')->nullable();
            $table->json('mutual_detail_slip_document')->nullable();
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

        DB::table('countries')->insert([
            'id' => 1,
            'name' => 'United Kingdom',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('car_reservations', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('driver_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->date('reservation_date')->nullable();
            $table->date('pick_up_date')->nullable();
            $table->date('available_from_date')->nullable();
            $table->decimal('agreed_rent', 12, 2)->nullable();
            $table->decimal('agreed_advance', 12, 2)->nullable();
            $table->decimal('amount_paid', 12, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->string('posting_status', 20)->nullable();
            $table->unsignedBigInteger('converted_agreement_id')->nullable();
            $table->decimal('balance_payable_on_pickup', 12, 2)->nullable();
            $table->foreignId('created_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('company_id');
            $table->string('bank_name');
            $table->string('account_number', 50);
            $table->timestamps();
        });

        Schema::create('daily_financial_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->date('sheet_date');
            $table->string('status', 20)->default('approved');
            $table->decimal('cash_in', 12, 2)->default(0);
            $table->decimal('cash_out', 12, 2)->default(0);
            $table->json('bank_in_json')->nullable();
            $table->json('bank_out_json')->nullable();
            $table->text('approval_notes')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('financial_sheet_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->date('sheet_date');
            $table->string('source_type');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('event_type');
            $table->string('direction');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('deposit_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('driver_id')->nullable();
            $table->foreignId('agreement_id')->nullable();
            $table->date('refund_date')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->string('posting_status', 20)->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('debt_payment_id')->nullable();
            $table->unsignedBigInteger('refund_credit_payment_id')->nullable();
            $table->timestamps();
        });

        Schema::create('driver_credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('driver_id')->nullable();
            $table->string('type');
            $table->date('request_date')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->string('posting_status', 20)->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('other_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->string('other_payment_type')->nullable();
            $table->unsignedBigInteger('car_id')->nullable();
            $table->string('title')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('posting_status', 20)->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->unsignedBigInteger('car_id')->nullable();
            $table->string('type')->nullable();
            $table->string('daily_expense_type')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->date('date')->nullable();
            $table->string('posting_status', 20)->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
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
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function agreementReadyDriverAttributes(int $tenantId, array $overrides = []): array
    {
        return array_merge([
            'tenant_id' => $tenantId,
            'first_name' => 'Test',
            'last_name' => 'Driver',
            'dob' => '1990-01-01',
            'email' => 'driver@example.com',
            'phone_number' => '07000000001',
            'address1' => '1 Test Street',
            'post_code' => 'SW1A 1AA',
            'town' => 'London',
            'country_id' => 1,
            'driver_license_number' => 'LIC000000',
            'driver_license_expiry_date' => '2027-01-01',
            'next_of_kin' => 'Jane Driver',
            'next_of_kin_phone' => '07000000099',
            'is_active' => true,
        ], $overrides);
    }
}
