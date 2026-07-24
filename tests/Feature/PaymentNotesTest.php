<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\TestCase;

class PaymentNotesTest extends TestCase
{
    private Tenant $tenant;

    private Company $company;

    private Driver $driver;

    private Payment $payment;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpDatabase();

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Notes Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->company = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Notes Company',
        ]);

        $this->driver = Driver::query()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Note',
            'last_name' => 'Driver',
            'email' => 'notes-driver@example.com',
            'is_active' => true,
        ]);

        $this->payment = Payment::query()->create([
            'driver_id' => $this->driver->id,
            'payment_method' => 'Card Payment',
            'payment_date' => now()->toDateString(),
            'amount' => 560,
            'notes' => 'PAID IN J-SUM',
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
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('agreements');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tenants');

        parent::tearDown();
    }

    public function test_driver_payments_page_shows_edit_notes_button(): void
    {
        $response = $this->get(route('payments.driver', $this->driver));

        $response->assertOk();
        $response->assertSee('PAID IN J-SUM');
        $response->assertSee('edit-payment-notes-btn');
        $response->assertSee('paymentNotesModal');
    }

    public function test_update_notes_saves_and_redirects_to_driver_payments_tab(): void
    {
        $redirectTo = route('payments.driver', $this->driver).'#payments';

        $response = $this->patch(route('payments.notes.update', $this->payment), [
            'notes' => 'Updated note text',
            'redirect_to' => $redirectTo,
        ]);

        $response->assertRedirect($redirectTo);
        $this->assertSame('Updated note text', $this->payment->fresh()->notes);
    }

    public function test_update_notes_can_clear_existing_note(): void
    {
        $response = $this->patch(route('payments.notes.update', $this->payment), [
            'notes' => '',
            'redirect_to' => route('payments.show', $this->payment),
        ]);

        $response->assertRedirect(route('payments.show', $this->payment));
        $this->assertNull($this->payment->fresh()->notes);
    }

    public function test_driver_payments_page_shows_agreement_link_for_agreement_invoice(): void
    {
        $agreement = Agreement::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'start_date' => now(),
            'end_date' => now()->addYear()->toDateString(),
            'agreed_rent' => 200,
            'rent_interval' => 'weekly',
            'deposit_amount' => 500,
            'collection_type' => 'weekly',
        ]);

        Invoice::query()->create([
            'driver_id' => $this->driver->id,
            'source_id' => $agreement->id,
            'invoice_type' => 'agreement',
            'invoice_no' => 'INV-AG-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'total_amount' => 200,
            'paid_amount' => 0,
            'balance_amount' => 200,
            'status' => 'pending',
        ]);

        $response = $this->get(route('payments.driver', $this->driver));

        $response->assertOk();
        $response->assertSee('Agreement ID #'.$agreement->id, false);
        $response->assertSee(route('agreements.show', $agreement), false);
    }

    public function test_driver_payments_page_does_not_show_agreement_link_for_manual_invoice(): void
    {
        Invoice::query()->create([
            'driver_id' => $this->driver->id,
            'invoice_type' => 'manual',
            'invoice_no' => 'INV-MAN-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'total_amount' => 100,
            'paid_amount' => 0,
            'balance_amount' => 100,
            'status' => 'pending',
        ]);

        $response = $this->get(route('payments.driver', $this->driver));

        $response->assertOk();
        $response->assertDontSee('Agreement ID #', false);
    }

    private function setUpDatabase(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
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

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_no')->nullable();
            $table->foreignId('driver_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->date('payment_date')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('posting_status', 20)->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->boolean('auto_allocate')->default(true);
            $table->unsignedBigInteger('allocation_source_id')->nullable();
            $table->json('allocation_invoice_types')->nullable();
            $table->json('pending_manual_allocations')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('invoice_type')->nullable();
            $table->string('invoice_no')->nullable();
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_amount', 12, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('driver_id')->nullable();
            $table->foreignId('car_id')->nullable();
            $table->foreignId('status_id')->nullable();
            $table->dateTime('start_date');
            $table->date('end_date');
            $table->decimal('agreed_rent', 10, 2)->default(0);
            $table->string('rent_interval')->nullable();
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->string('collection_type')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id');
            $table->foreignId('invoice_id');
            $table->decimal('allocated_amount', 12, 2)->default(0);
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
    }
}
