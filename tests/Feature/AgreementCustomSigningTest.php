<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\AgreementSignatureToken;
use App\Models\Car;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Status;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AgreementPdfService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\Concerns\SetupAgreementChangeCarDatabase;
use Tests\TestCase;

class AgreementCustomSigningTest extends TestCase
{
    use SetupAgreementChangeCarDatabase;

    private const SIGNATURE_PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private Tenant $tenant;

    private Company $company;

    private Driver $driver;

    private Car $car;

    private Status $activeStatus;

    private Agreement $agreement;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            RoleMiddleware::class,
            VerifyCsrfToken::class,
            ValidateCsrfToken::class,
        ]);
        $this->setUpAgreementChangeCarDatabase();
        $this->setUpHttpTestExtras();

        Carbon::setTestNow(Carbon::parse('2026-08-19 10:00:00'));
        config([
            'mail.from.address' => 'noreply@example.com',
            'mail.from.name' => 'FleetIQ',
        ]);

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Sign Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $this->company = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Sign Company',
        ]);
        $this->driver = Driver::query()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Sam',
            'last_name' => 'Driver',
            'email' => 'sam-sign@example.com',
            'phone_number' => '07000000009',
        ]);

        $carModelId = DB::table('car_models')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'name' => 'Sign Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $counselId = DB::table('counsels')->insertGetId([
            'name' => 'Sign Counsel',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->car = $this->createCompliantCar($carModelId, $counselId);

        $this->activeStatus = Status::query()->create(['name' => 'Active', 'type' => 'agreement']);

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
            'car_id' => $this->car->id,
            'start_date' => '2026-08-01',
            'end_date' => '2027-08-01',
            'agreed_rent' => 150,
            'rent_interval' => 'weekly',
            'deposit_amount' => 200,
            'collection_type' => 'weekly',
            'status_id' => $this->activeStatus->id,
            'using_own_insurance' => false,
            'createdBy' => $this->user->id,
            'updatedBy' => $this->user->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Schema::dropIfExists('agreement_signature_tokens');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('tenant_user');
        $this->tearDownAgreementChangeCarDatabase();

        parent::tearDown();
    }

    public function test_send_signature_requires_driver_email(): void
    {
        $this->driver->update(['email' => null]);

        $response = $this->from(route('agreements.show', $this->agreement))
            ->post(route('agreements.send-esign', $this->agreement));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame(0, AgreementSignatureToken::query()->count());
    }

    public function test_send_signature_creates_token_and_sends_mail(): void
    {
        Mail::fake();

        $response = $this->post(route('agreements.send-esign', $this->agreement));

        $response->assertRedirect(route('agreements.show', $this->agreement));
        $response->assertSessionHasNoErrors();

        $token = AgreementSignatureToken::query()->where('agreement_id', $this->agreement->id)->first();
        $this->assertNotNull($token);
        $this->assertSame('pending', $token->status);
        $this->assertSame('sam-sign@example.com', $token->signer_email);
        $this->agreement->refresh();
        $this->assertSame('pending', $this->agreement->hellosign_status);
    }

    public function test_public_sign_link_logs_ip_and_referrer_without_auth(): void
    {
        $token = $this->createPendingToken();

        auth()->logout();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->withHeader('referer', 'https://mail.example.com/inbox')
            ->withHeader('User-Agent', 'Mozilla/5.0 TestBrowser')
            ->get(route('sign.show', $token->token))
            ->assertOk()
            ->assertSee('Read Your Agreement')
            ->assertSee('Type name');

        $token->refresh();
        $this->assertNotNull($token->opened_at);
        $this->assertSame('203.0.113.10', $token->opened_ip);
        $this->assertSame('https://mail.example.com/inbox', $token->referrer);
        $this->assertStringContainsString('TestBrowser', (string) $token->user_agent);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->get(route('sign.show', $token->token))
            ->assertOk();

        $token->refresh();
        $this->assertSame('203.0.113.10', $token->opened_ip);
    }

    public function test_submit_drawn_signature_marks_agreement_signed(): void
    {
        $token = $this->createPendingToken();
        auth()->logout();

        $response = $this->postJson(route('sign.submit', $token->token), [
            'signature' => self::SIGNATURE_PNG,
            'signature_method' => 'draw',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $token->refresh();
        $this->assertTrue($token->isSigned());
        $this->assertSame('draw', $token->signature_method);
        $this->agreement->refresh();
        $this->assertSame('signed', $this->agreement->hellosign_status);
        $this->assertNotNull($this->agreement->esign_document_path);
        $this->assertFileExists(storage_path('app/'.$this->agreement->esign_document_path));
    }

    public function test_submit_typed_name_marks_agreement_signed(): void
    {
        $token = $this->createPendingToken();
        auth()->logout();

        $response = $this->postJson(route('sign.submit', $token->token), [
            'signature' => self::SIGNATURE_PNG,
            'signature_method' => 'typed',
            'typed_name' => 'Sam Driver',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $token->refresh();
        $this->assertTrue($token->isSigned());
        $this->assertSame('typed', $token->signature_method);
        $this->assertSame('Sam Driver', $token->typed_name);
        $this->agreement->refresh();
        $this->assertSame('signed', $this->agreement->hellosign_status);
    }

    public function test_signature_succeeds_when_signed_pdf_cannot_be_written(): void
    {
        $token = $this->createPendingToken();
        $blocked = storage_path('app/agreements/signed');
        $frameworkBlocked = storage_path('framework/agreements/signed');
        File::ensureDirectoryExists($blocked);
        File::ensureDirectoryExists($frameworkBlocked);
        chmod($blocked, 0555);
        chmod($frameworkBlocked, 0555);

        try {
            $result = app(\App\Services\CustomSigningService::class)->processSignature(
                $token,
                self::SIGNATURE_PNG,
                '127.0.0.1',
                ['signature_method' => 'draw']
            );
        } finally {
            chmod($blocked, 0775);
            chmod($frameworkBlocked, 0775);
        }

        $this->assertTrue($result['success']);
        $token->refresh();
        $this->assertTrue($token->isSigned());
        $this->agreement->refresh();
        $this->assertSame('signed', $this->agreement->hellosign_status);
    }

    public function test_signed_agreement_pdf_includes_signature_image(): void
    {
        $token = $this->createPendingToken();
        app(\App\Services\CustomSigningService::class)->processSignature(
            $token,
            self::SIGNATURE_PNG,
            '127.0.0.1',
            ['signature_method' => 'draw']
        );

        $data = app(AgreementPdfService::class)->agreementPdfViewData($this->agreement->fresh());
        $this->assertSame(self::SIGNATURE_PNG, $data['signature_image']);

        [$pdf] = app(AgreementPdfService::class)->makeAgreementPdf($this->agreement->fresh());
        $this->assertNotEmpty($pdf->output());
    }

    public function test_expired_and_already_signed_links_reject_new_signature(): void
    {
        $expired = $this->createPendingToken(['expires_at' => now()->subHour()]);
        auth()->logout();

        $this->get(route('sign.show', $expired->token))->assertOk()->assertSee('Signing Link Expired');

        $this->postJson(route('sign.submit', $expired->token), [
            'signature' => self::SIGNATURE_PNG,
            'signature_method' => 'draw',
        ])->assertStatus(400);

        $token = $this->createPendingToken();
        $this->postJson(route('sign.submit', $token->token), [
            'signature' => self::SIGNATURE_PNG,
            'signature_method' => 'draw',
        ])->assertOk();

        $this->postJson(route('sign.submit', $token->token), [
            'signature' => self::SIGNATURE_PNG,
            'signature_method' => 'draw',
        ])->assertStatus(400);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createPendingToken(array $overrides = []): AgreementSignatureToken
    {
        $this->agreement->update([
            'hellosign_status' => 'pending',
            'esign_sent_at' => now(),
        ]);

        return AgreementSignatureToken::query()->create(array_merge([
            'agreement_id' => $this->agreement->id,
            'token' => AgreementSignatureToken::generateToken(),
            'signer_email' => $this->driver->email,
            'signer_name' => 'Sam Driver',
            'status' => 'pending',
            'expires_at' => now()->addHours(72),
        ], $overrides));
    }

    private function createCompliantCar(int $carModelId, int $counselId): Car
    {
        $car = Car::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'car_model_id' => $carModelId,
            'registration' => 'SIG123',
            'color' => 'White',
            'vin' => 'VINSIGN123',
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

    private function setUpHttpTestExtras(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->default(1);
        });
        Schema::table('agreements', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_agreement_id')->nullable();
            $table->string('hellosign_request_id')->nullable();
            $table->string('hellosign_sign_url')->nullable();
            $table->string('hellosign_status')->nullable();
            $table->timestamp('esign_sent_at')->nullable();
            $table->timestamp('esign_completed_at')->nullable();
            $table->string('esign_document_path')->nullable();
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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->string('esign_provider')->default('custom');
            $table->timestamps();
        });
        Schema::create('agreement_signature_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id');
            $table->string('token', 100)->unique();
            $table->string('signer_email');
            $table->string('signer_name');
            $table->string('status')->default('pending');
            $table->longText('signature_data')->nullable();
            $table->string('signature_method', 20)->nullable();
            $table->string('typed_name', 255)->nullable();
            $table->string('ip_address', 50)->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('opened_at')->nullable();
            $table->string('opened_ip', 45)->nullable();
            $table->text('referrer')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('accept_language', 255)->nullable();
            $table->text('landing_url')->nullable();
            $table->timestamps();
        });
    }
}
