<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Driver;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\TestCase;

class PaymentFollowUpReminderTest extends TestCase
{
    private Tenant $tenant;

    private Driver $driver;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpDatabase();

        Carbon::setTestNow(Carbon::parse('2026-07-20 15:00:00'));

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Follow Up Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Follow Up Company',
        ]);

        $this->driver = Driver::query()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Call',
            'last_name' => 'Me',
            'email' => 'call-me@example.com',
            'phone_number' => '07000000111',
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
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_save_notes_only_without_reminder(): void
    {
        $response = $this->patchJson(route('payments.follow-up.update', $this->driver), [
            'notes' => 'Call after 5pm about balance',
            'set_reminder' => 0,
        ]);

        $response->assertOk();
        $response->assertJsonPath('driver.has_note', true);
        $response->assertJsonPath('driver.has_reminder', false);

        $this->driver->refresh();
        $this->assertSame('Call after 5pm about balance', $this->driver->payment_follow_up_notes);
        $this->assertNull($this->driver->payment_remind_at);

        $due = $this->getJson(route('payments.follow-up.due'));
        $due->assertOk();
        $this->assertCount(0, $due->json('reminders'));
    }

    public function test_future_reminder_is_not_in_due_list(): void
    {
        $response = $this->patchJson(route('payments.follow-up.update', $this->driver), [
            'notes' => 'Call tomorrow',
            'set_reminder' => 1,
            'remind_at' => '2026-07-21T10:00:00.000Z',
        ]);

        $response->assertOk();
        $response->assertJsonPath('driver.has_reminder', true);

        $due = $this->getJson(route('payments.follow-up.due'));
        $due->assertOk();
        $this->assertCount(0, $due->json('reminders'));
    }

    public function test_iso_remind_at_in_the_past_is_due_immediately(): void
    {
        $response = $this->patchJson(route('payments.follow-up.update', $this->driver), [
            'notes' => 'Call now',
            'set_reminder' => 1,
            'remind_at' => '2026-07-20T14:55:00.000Z',
        ]);

        $response->assertOk();
        $this->driver->refresh();
        $this->assertTrue($this->driver->isPaymentReminderDue());

        $due = $this->getJson(route('payments.follow-up.due'));
        $due->assertJsonCount(1, 'reminders');
    }

    public function test_past_reminder_appears_in_due_list(): void
    {
        $this->driver->update([
            'payment_follow_up_notes' => 'Driver said call at 2pm',
            'payment_remind_at' => '2026-07-20 14:00:00',
            'payment_reminder_dismissed_at' => null,
        ]);

        $due = $this->getJson(route('payments.follow-up.due'));
        $due->assertOk();
        $due->assertJsonCount(1, 'reminders');
        $due->assertJsonPath('reminders.0.id', $this->driver->id);
        $due->assertJsonPath('reminders.0.notes', 'Driver said call at 2pm');
        $due->assertJsonPath('reminders.0.phone', '07000000111');
    }

    public function test_dismiss_removes_from_due_list_but_keeps_notes(): void
    {
        $this->driver->update([
            'payment_follow_up_notes' => 'Keep this note',
            'payment_remind_at' => '2026-07-20 14:00:00',
            'payment_reminder_dismissed_at' => null,
        ]);

        $response = $this->postJson(route('payments.follow-up.dismiss', $this->driver));
        $response->assertOk();

        $this->driver->refresh();
        $this->assertSame('Keep this note', $this->driver->payment_follow_up_notes);
        $this->assertNotNull($this->driver->payment_reminder_dismissed_at);

        $due = $this->getJson(route('payments.follow-up.due'));
        $this->assertCount(0, $due->json('reminders'));
    }

    public function test_clearing_reminder_sets_remind_at_null(): void
    {
        $this->driver->update([
            'payment_follow_up_notes' => 'Note stays',
            'payment_remind_at' => '2026-07-21 10:00:00',
        ]);

        $response = $this->patchJson(route('payments.follow-up.update', $this->driver), [
            'notes' => 'Note stays',
            'set_reminder' => 0,
        ]);

        $response->assertOk();
        $this->driver->refresh();
        $this->assertSame('Note stays', $this->driver->payment_follow_up_notes);
        $this->assertNull($this->driver->payment_remind_at);
    }

    public function test_cannot_update_follow_up_for_other_tenant_driver(): void
    {
        $otherTenant = Tenant::query()->create([
            'company_name' => 'Other Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $otherDriver = Driver::query()->create([
            'tenant_id' => $otherTenant->id,
            'first_name' => 'Other',
            'last_name' => 'Driver',
            'email' => 'other@example.com',
            'is_active' => true,
        ]);

        $response = $this->patchJson(route('payments.follow-up.update', $otherDriver), [
            'notes' => 'Should fail',
            'set_reminder' => 0,
        ]);

        $response->assertForbidden();
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
            $table->string('phone_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('payment_follow_up_notes')->nullable();
            $table->dateTime('payment_remind_at')->nullable();
            $table->dateTime('payment_reminder_dismissed_at')->nullable();
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
