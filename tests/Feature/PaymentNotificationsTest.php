<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
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
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        Schema::dropIfExists('tenant_user');
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
}
