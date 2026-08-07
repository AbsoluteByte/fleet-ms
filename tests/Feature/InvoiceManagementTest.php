<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Invoice;
use App\Models\PaymentAllocation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\TestCase;

class InvoiceManagementTest extends TestCase
{
    private Tenant $tenant;

    private User $manager;

    private User $employee;

    private Driver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpSchema();

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Invoice Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $this->driver = Driver::query()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Ali',
            'last_name' => 'Khan',
            'email' => 'ali@example.com',
        ]);

        $this->manager = User::factory()->create(['email' => 'jawad@samoretraders.com']);
        $this->employee = User::factory()->create(['email' => 'staff@example.com']);
        $this->manager->tenants()->attach($this->tenant->id, ['role' => 'admin', 'is_primary' => true, 'joined_at' => now()]);
        $this->employee->tenants()->attach($this->tenant->id, ['role' => 'admin', 'is_primary' => true, 'joined_at' => now()]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tenants');

        parent::tearDown();
    }

    public function test_jawad_can_update_invoice_total(): void
    {
        $invoice = $this->createInvoice(200);

        $this->actingAs($this->manager);
        $this->manager->switchTenant($this->tenant->id);

        $response = $this->patch(route('invoices.update', $invoice), [
            'subtotal' => 180,
            'total_amount' => 150,
        ]);

        $response->assertRedirect();
        $invoice->refresh();
        $this->assertEquals(150, (float) $invoice->total_amount);
        $this->assertEquals(150, (float) $invoice->balance_amount);
    }

    public function test_non_manager_cannot_update_invoice(): void
    {
        $invoice = $this->createInvoice(200);

        $this->actingAs($this->employee);
        $this->employee->switchTenant($this->tenant->id);

        $this->patch(route('invoices.update', $invoice), [
            'subtotal' => 180,
            'total_amount' => 150,
        ])->assertForbidden();
    }

    public function test_jawad_can_delete_unallocated_invoice(): void
    {
        $invoice = $this->createInvoice(200);

        $this->actingAs($this->manager);
        $this->manager->switchTenant($this->tenant->id);

        $this->delete(route('invoices.destroy', $invoice))
            ->assertRedirect();

        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
    }

    public function test_jawad_cannot_delete_invoice_with_allocations(): void
    {
        $invoice = $this->createInvoice(200);
        PaymentAllocation::query()->create([
            'payment_id' => 1,
            'invoice_id' => $invoice->id,
            'allocated_amount' => 50,
        ]);

        $this->actingAs($this->manager);
        $this->manager->switchTenant($this->tenant->id);

        $this->delete(route('invoices.destroy', $invoice))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }

    private function createInvoice(float $total): Invoice
    {
        return Invoice::query()->create([
            'driver_id' => $this->driver->id,
            'invoice_type' => 'agreement',
            'source_id' => 1,
            'invoice_date' => '2026-06-15',
            'due_date' => '2026-06-15',
            'subtotal' => $total,
            'total_amount' => $total,
            'paid_amount' => 0,
            'balance_amount' => $total,
            'status' => 'pending',
        ]);
    }

    private function setUpSchema(): void
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

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->nullable();
            $table->foreignId('driver_id');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('invoice_type');
            $table->date('invoice_date')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_amount', 12, 2)->default(0);
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_id');
            $table->foreignId('invoice_id');
            $table->decimal('allocated_amount', 12, 2);
            $table->timestamps();
        });
    }
}
