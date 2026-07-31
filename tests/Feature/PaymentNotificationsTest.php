<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Car;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\Payment;
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

class PaymentNotificationsTest extends TestCase
{
    use SetupAgreementChangeCarDatabase;

    private Tenant $tenant;

    private Driver $driver;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpAgreementChangeCarDatabase();
        $this->setUpHttpTestExtras();

        Carbon::setTestNow(Carbon::parse('2026-07-20 10:00:00'));

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Payment Notifications Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->driver = Driver::query()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Notify',
            'last_name' => 'Driver',
            'email' => 'notify-driver@example.com',
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create(['email' => 'admin-notify@example.com']);
        $this->user->tenants()->attach($this->tenant->id, [
            'role' => 'admin',
            'is_primary' => true,
            'joined_at' => now(),
        ]);

        $this->actingAs($this->user);
        $this->user->switchTenant($this->tenant->id);
        $this->user->assignRole('admin');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('car_services');
        $this->tearDownAgreementChangeCarDatabase();

        parent::tearDown();
    }

    public function test_generated_today_includes_invoice_created_today_even_when_due_later(): void
    {
        $invoice = $this->createInvoice([
            'invoice_date' => '2026-07-20',
            'due_date' => '2026-07-27',
            'balance_amount' => 100,
        ]);

        $invoiceIds = $this->paymentNotificationInvoiceIds('due_today');

        $this->assertContains($invoice->id, $invoiceIds);
    }

    public function test_generated_today_excludes_invoice_due_today_but_created_yesterday(): void
    {
        $invoice = $this->createInvoice([
            'invoice_date' => '2026-07-19',
            'due_date' => '2026-07-20',
            'balance_amount' => 75,
        ]);

        $invoiceIds = $this->paymentNotificationInvoiceIds('due_today');

        $this->assertNotContains($invoice->id, $invoiceIds);
    }

    public function test_invoice_generated_today_is_not_duplicated_in_due_this_week(): void
    {
        $invoice = $this->createInvoice([
            'invoice_date' => '2026-07-20',
            'due_date' => '2026-07-27',
            'balance_amount' => 120,
        ]);

        $generatedTodayIds = $this->paymentNotificationInvoiceIds('due_today');
        $dueThisWeekIds = $this->paymentNotificationInvoiceIds('due_this_week');

        $this->assertContains($invoice->id, $generatedTodayIds);
        $this->assertNotContains($invoice->id, $dueThisWeekIds);
    }

    public function test_ajax_row_includes_generated_and_due_dates(): void
    {
        $invoice = $this->createInvoice([
            'invoice_date' => '2026-07-20',
            'due_date' => '2026-07-27',
            'balance_amount' => 100,
        ]);

        $row = $this->paymentNotificationRowForInvoice($invoice->id, 'due_today');

        $this->assertNotNull($row);
        $this->assertSame('20 Jul, 2026', $row['invoice_generated_date']);
        $this->assertSame('27 Jul, 2026', $row['due_date']);
    }

    public function test_all_payments_date_range_returns_only_invoices_within_range(): void
    {
        $inRange = $this->createInvoice([
            'invoice_date' => '2026-07-18',
            'due_date' => '2026-07-25',
            'balance_amount' => 80,
        ]);
        $outOfRange = $this->createInvoice([
            'invoice_date' => '2026-07-10',
            'due_date' => '2026-07-12',
            'balance_amount' => 90,
        ]);

        $invoiceIds = $this->paymentNotificationInvoiceIds('', [
            'invoice_date_from' => '2026-07-15',
            'invoice_date_to' => '2026-07-20',
        ]);

        $this->assertContains($inRange->id, $invoiceIds);
        $this->assertNotContains($outOfRange->id, $invoiceIds);
    }

    public function test_overdue_tab_ignores_generated_date_range_params(): void
    {
        $invoice = $this->createInvoice([
            'invoice_date' => '2026-07-10',
            'due_date' => '2026-07-15',
            'balance_amount' => 50,
        ]);

        $invoiceIds = $this->paymentNotificationInvoiceIds('overdue_payment', [
            'invoice_date_from' => '2026-07-18',
            'invoice_date_to' => '2026-07-20',
        ]);

        $this->assertContains($invoice->id, $invoiceIds);
    }

    public function test_ajax_row_includes_driver_follow_up_fields(): void
    {
        $this->driver->update([
            'payment_follow_up_notes' => 'Call driver about overdue rent',
            'payment_remind_at' => '2026-07-21 15:00:00',
            'payment_reminder_dismissed_at' => null,
        ]);

        $invoice = $this->createInvoice([
            'invoice_date' => '2026-07-10',
            'due_date' => '2026-07-15',
            'balance_amount' => 50,
        ]);

        $row = $this->paymentNotificationRowForInvoice($invoice->id, 'overdue_payment');

        $this->assertNotNull($row);
        $this->assertTrue($row['follow_up_has_note']);
        $this->assertTrue($row['follow_up_has_reminder']);
        $this->assertSame('Call driver about overdue rent', $row['follow_up_notes']);
        $this->assertNotEmpty($row['follow_up_remind_at']);
        $this->assertSame(route('payments.follow-up.update', $this->driver), $row['follow_up_update_url']);
    }

    public function test_notifications_page_includes_follow_up_modal(): void
    {
        $response = $this->get(route('payments.notifications'));

        $response->assertOk();
        $response->assertSee('id="driverFollowUpModal"', false);
        $response->assertSee('js-driver-follow-up', false);
    }

    public function test_overdue_notification_uses_warning_amount_when_pending_dfs_payment_exists(): void
    {
        Payment::query()->create([
            'driver_id' => $this->driver->id,
            'payment_method' => 'Cash',
            'payment_date' => '2026-07-19',
            'amount' => 80,
            'posting_status' => Payment::POSTING_STATUS_PENDING,
            'auto_allocate' => false,
            'created_by' => $this->user->id,
        ]);

        $invoice = $this->createInvoice([
            'invoice_date' => '2026-07-10',
            'due_date' => '2026-07-15',
            'balance_amount' => 50,
        ]);

        $row = $this->paymentNotificationRowForInvoice($invoice->id, 'overdue_payment');

        $this->assertNotNull($row);
        $this->assertSame('warning', $row['amount_color']);
        $this->assertSame('£80.00 pending daily financial sheet approval.', $row['amount_tooltip']);
        $this->assertSame('warning', $row['color']);
    }

    public function test_overdue_notification_keeps_danger_amount_without_pending_dfs_payment(): void
    {
        $invoice = $this->createInvoice([
            'invoice_date' => '2026-07-10',
            'due_date' => '2026-07-15',
            'balance_amount' => 50,
        ]);

        $row = $this->paymentNotificationRowForInvoice($invoice->id, 'overdue_payment');

        $this->assertNotNull($row);
        $this->assertSame('danger', $row['amount_color']);
        $this->assertNull($row['amount_tooltip']);
    }

    public function test_due_today_notification_amount_is_danger_without_pending_dfs(): void
    {
        $invoice = $this->createInvoice([
            'invoice_date' => '2026-07-20',
            'due_date' => '2026-07-27',
            'balance_amount' => 100,
        ]);

        $row = $this->paymentNotificationRowForInvoice($invoice->id, 'due_today');

        $this->assertNotNull($row);
        $this->assertSame('danger', $row['amount_color']);
        $this->assertNull($row['amount_tooltip']);
        $this->assertSame('danger', $row['color']);
    }

    public function test_due_today_notification_uses_warning_when_pending_dfs_exists(): void
    {
        Payment::query()->create([
            'driver_id' => $this->driver->id,
            'payment_method' => 'Cash',
            'payment_date' => '2026-07-19',
            'amount' => 60,
            'posting_status' => Payment::POSTING_STATUS_PENDING,
            'auto_allocate' => false,
            'created_by' => $this->user->id,
        ]);

        $invoice = $this->createInvoice([
            'invoice_date' => '2026-07-20',
            'due_date' => '2026-07-27',
            'balance_amount' => 100,
        ]);

        $row = $this->paymentNotificationRowForInvoice($invoice->id, 'due_today');

        $this->assertNotNull($row);
        $this->assertSame('warning', $row['amount_color']);
        $this->assertSame('£60.00 pending daily financial sheet approval.', $row['amount_tooltip']);
        $this->assertSame('warning', $row['color']);
    }

    public function test_due_this_week_notification_amount_is_danger_without_pending_dfs(): void
    {
        $invoice = $this->createInvoice([
            'invoice_date' => '2026-07-18',
            'due_date' => '2026-07-24',
            'balance_amount' => 90,
        ]);

        $row = $this->paymentNotificationRowForInvoice($invoice->id, 'due_this_week');

        $this->assertNotNull($row);
        $this->assertSame('danger', $row['amount_color']);
        $this->assertNull($row['amount_tooltip']);
        $this->assertSame('danger', $row['color']);
    }

    public function test_payment_notification_includes_replacement_agreement_car_registration(): void
    {
        [$parentAgreement] = $this->createAgreementWithReplacement('REG111', 'REPREPL');

        $invoice = $this->createInvoice([
            'invoice_type' => 'agreement',
            'source_id' => $parentAgreement->id,
            'invoice_date' => '2026-07-10',
            'due_date' => '2026-07-15',
            'balance_amount' => 100,
        ]);

        $row = $this->paymentNotificationRowForInvoice($invoice->id, 'overdue_payment');

        $this->assertNotNull($row);
        $this->assertSame('REG111, REPREPL', $row['vehicle']);
    }

    public function test_payment_notification_vehicle_is_searchable_by_replacement_registration(): void
    {
        [$parentAgreement] = $this->createAgreementWithReplacement('REG111', 'REPREPL');

        $invoice = $this->createInvoice([
            'invoice_type' => 'agreement',
            'source_id' => $parentAgreement->id,
            'invoice_date' => '2026-07-10',
            'due_date' => '2026-07-15',
            'balance_amount' => 100,
        ]);

        $response = $this->get(route('payments.notifications', [
            'type' => 'overdue_payment',
        ]), [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ]);

        $response->assertOk();

        $matchingRows = collect($response->json('data'))
            ->filter(fn (array $row) => str_contains((string) ($row['vehicle'] ?? ''), 'REPREPL'));

        $this->assertTrue($matchingRows->contains('invoice_id', $invoice->id));
    }

    public function test_notifications_page_merges_paying_company_into_driver_column(): void
    {
        $response = $this->get(route('payments.notifications'));

        $response->assertOk();
        $response->assertSee('formatDriverWithPayingCompany', false);
        $response->assertDontSee('>PAYS VIA</th>', false);
        $response->assertSee('>DRIVER</th>', false);
    }

    public function test_payment_notification_includes_paying_company_in_row_data(): void
    {
        $company = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Notify Co',
        ]);
        $activeStatus = Status::query()->create([
            'name' => 'Active',
            'type' => 'agreement',
        ]);
        $carModelId = (int) DB::table('car_models')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'name' => 'Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $car = Car::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $company->id,
            'car_model_id' => $carModelId,
            'registration' => 'NOTIFCO',
            'color' => 'Black',
            'fleet_status' => Car::FLEET_STATUS_AVAILABLE_FOR_RENT,
        ]);
        $agreement = Agreement::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $car->id,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth()->toDateString(),
            'agreed_rent' => 200,
            'rent_interval' => 'Weekly',
            'deposit_amount' => 300,
            'collection_type' => 'weekly',
            'status_id' => $activeStatus->id,
            'paying_company_name' => 'Metro Cars PLC',
        ]);

        $invoice = $this->createInvoice([
            'invoice_type' => 'agreement',
            'source_id' => $agreement->id,
            'invoice_date' => '2026-07-10',
            'due_date' => '2026-07-15',
            'balance_amount' => 100,
        ]);

        $row = $this->paymentNotificationRowForInvoice($invoice->id, 'overdue_payment');

        $this->assertNotNull($row);
        $this->assertSame('Metro Cars PLC', $row['paying_company']);
    }

    /**
     * @return list<int>
     */
    private function paymentNotificationInvoiceIds(string $type, array $query = []): array
    {
        $response = $this->get(route('payments.notifications', array_merge([
            'type' => $type,
        ], $query)), [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ]);

        $response->assertOk();

        return collect($response->json('data'))
            ->pluck('invoice_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function paymentNotificationRowForInvoice(int $invoiceId, string $type = ''): ?array
    {
        $response = $this->get(route('payments.notifications', [
            'type' => $type,
        ]), [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ]);

        $response->assertOk();

        return collect($response->json('data'))
            ->firstWhere('invoice_id', $invoiceId);
    }

    private function createInvoice(array $overrides = []): Invoice
    {
        $amount = (float) ($overrides['balance_amount'] ?? 100);

        return Invoice::query()->create(array_merge([
            'driver_id' => $this->driver->id,
            'invoice_type' => 'manual',
            'invoice_no' => 'INV-'.random_int(1000, 9999),
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'total_amount' => $amount,
            'paid_amount' => 0,
            'balance_amount' => $amount,
            'status' => 'pending',
        ], $overrides));
    }

    /**
     * @return array{0: Agreement, 1: Agreement}
     */
    private function createAgreementWithReplacement(string $activeRegistration, string $replacementRegistration): array
    {
        $company = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Notifications Company',
        ]);

        $activeStatus = Status::query()->create([
            'name' => 'Active',
            'type' => 'agreement',
        ]);
        $replacementStatus = Status::query()->create([
            'name' => 'Replacement Vehicle',
            'type' => 'agreement',
        ]);

        $carModelId = (int) DB::table('car_models')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'name' => 'Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $activeCar = Car::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $company->id,
            'car_model_id' => $carModelId,
            'registration' => $activeRegistration,
            'color' => 'Black',
            'fleet_status' => Car::FLEET_STATUS_AVAILABLE_FOR_RENT,
        ]);
        $replacementCar = Car::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $company->id,
            'car_model_id' => $carModelId,
            'registration' => $replacementRegistration,
            'color' => 'White',
            'fleet_status' => Car::FLEET_STATUS_AVAILABLE_FOR_RENT,
        ]);

        $parentAgreement = Agreement::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $activeCar->id,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth()->toDateString(),
            'agreed_rent' => 200,
            'rent_interval' => 'Weekly',
            'deposit_amount' => 300,
            'collection_type' => 'weekly',
            'status_id' => $activeStatus->id,
        ]);

        $replacementAgreement = Agreement::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $replacementCar->id,
            'parent_agreement_id' => $parentAgreement->id,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth()->toDateString(),
            'agreed_rent' => 0,
            'rent_interval' => 'Weekly',
            'deposit_amount' => 0,
            'collection_type' => 'weekly',
            'status_id' => $replacementStatus->id,
        ]);

        return [$parentAgreement, $replacementAgreement];
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
            $table->text('payment_follow_up_notes')->nullable();
            $table->dateTime('payment_remind_at')->nullable();
            $table->dateTime('payment_reminder_dismissed_at')->nullable();
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

        Schema::table('agreements', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_agreement_id')->nullable();
            $table->string('paying_company_name')->nullable();
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

        DB::table('roles')->insert([
            'id' => 1,
            'name' => 'admin',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
