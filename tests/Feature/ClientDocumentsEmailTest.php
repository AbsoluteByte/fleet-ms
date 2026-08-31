<?php

namespace Tests\Feature;

use App\Mail\AgreementClientDocumentsMail;
use App\Models\Agreement;
use App\Models\Car;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Status;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AgreementClientDocumentsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\SetupAgreementChangeCarDatabase;
use Tests\TestCase;

class ClientDocumentsEmailTest extends TestCase
{
    use SetupAgreementChangeCarDatabase;

    private Tenant $tenant;

    private Company $company;

    private Driver $driver;

    private Car $car;

    private Status $agreementStatus;

    private Status $insuranceActiveStatus;

    private User $user;

    private AgreementClientDocumentsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAgreementChangeCarDatabase();
        $this->setUpHttpTestExtras();
        $this->service = app(AgreementClientDocumentsService::class);

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Docs Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $this->company = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Docs Company',
        ]);
        $this->driver = Driver::query()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Docs',
            'last_name' => 'Driver',
            'email' => 'docs-driver@example.com',
            'driver_license_document' => 'driver-license.pdf',
            'dvla_license_summary' => 'dvla-summary.pdf',
            'driver_phd_license_document' => 'private-hire-license.pdf',
            'phd_card_document' => 'private-hire-card.pdf',
        ]);

        $carModelId = (int) DB::table('car_models')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'name' => 'Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $counselId = (int) DB::table('counsels')->insertGetId([
            'name' => 'Council',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->car = Car::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'car_model_id' => $carModelId,
            'registration' => 'DOCS123',
            'color' => 'Black',
            'fleet_status' => Car::FLEET_STATUS_AVAILABLE_FOR_RENT,
            'v5_document' => ['v5-logbook.pdf'],
        ]);

        DB::table('car_phvs')->insert([
            'car_id' => $this->car->id,
            'counsel_id' => $counselId,
            'amount' => 100,
            'start_date' => now()->subMonth()->toDateString(),
            'expiry_date' => now()->addMonth()->toDateString(),
            'notify_before_expiry' => 30,
            'document' => 'latest-phv.pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('car_mots')->insert([
            'car_id' => $this->car->id,
            'expiry_date' => now()->addMonth()->toDateString(),
            'amount' => 50,
            'term' => '12 months',
            'document' => 'latest-mot.pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->agreementStatus = Status::query()->create(['name' => 'Active', 'type' => 'agreement']);
        $this->insuranceActiveStatus = Status::query()->create(['name' => 'Active', 'type' => 'insurance']);

        DB::table('insurance_providers')->insert([
            'tenant_id' => $this->tenant->id,
            'provider_name' => 'Provider',
            'policy_number' => 'POL-123',
            'expiry_date' => now()->addMonth()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $providerId = (int) DB::table('insurance_providers')->value('id');

        DB::table('car_insurances')->insert([
            'tenant_id' => $this->tenant->id,
            'car_id' => $this->car->id,
            'insurance_provider_id' => $providerId,
            'start_date' => now()->subMonth()->toDateString(),
            'expiry_date' => now()->addMonth()->toDateString(),
            'insurance_document' => 'company-insurance.pdf',
            'status_id' => $this->insuranceActiveStatus->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->user = User::factory()->create();
        $this->user->tenants()->attach($this->tenant->id, [
            'role' => 'admin',
            'is_primary' => true,
            'joined_at' => now(),
        ]);
        $roleId = DB::table('roles')->insertGetId([
            'name' => 'admin',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('model_has_roles')->insert([
            'role_id' => $roleId,
            'model_type' => User::class,
            'model_id' => $this->user->id,
        ]);
        $this->actingAs($this->user);
        $this->user->switchTenant($this->tenant->id);

        $this->seedUploadFiles();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('agreement_signature_tokens');
        Schema::dropIfExists('car_services');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('insurance_providers');
        $this->tearDownAgreementChangeCarDatabase();

        File::deleteDirectory(public_path('uploads/driver_licenses'));
        File::deleteDirectory(public_path('uploads/cars/phv_documents'));
        File::deleteDirectory(public_path('uploads/cars/mot_documents'));
        File::deleteDirectory(public_path('uploads/cars/insurance_documents'));
        File::deleteDirectory(public_path('uploads/cars'));
        File::deleteDirectory(public_path('uploads/insurance_documents'));
        File::deleteDirectory(storage_path('app/temp/agreement_client_docs'));

        parent::tearDown();
    }

    public function test_collects_company_insurance_document_when_company_insurance_selected(): void
    {
        $agreement = $this->createAgreement(false, null);

        $payload = $this->service->collectForAgreement($agreement);

        $this->assertContains('Company insurance document', $payload['attachedLabels']);
        $this->assertContains('Private Hire Driver license', $payload['attachedLabels']);
        $this->assertContains('Private Hire Driver card', $payload['attachedLabels']);
        $this->assertContains('Agreement PDF', $payload['attachedLabels']);
        $this->assertContains('Permission letter', $payload['attachedLabels']);
        $this->assertNotContains('Agreement PDF', $payload['missingDocuments']);
        $this->assertNotContains('Permission letter', $payload['missingDocuments']);
    }

    public function test_collects_client_insurance_documents_when_client_insurance_selected(): void
    {
        $agreement = $this->createAgreement(true, ['client-insurance-a.pdf', 'client-insurance-b.pdf']);
        File::put(public_path('uploads/insurance_documents/client-insurance-b.pdf'), 'dummy');

        $payload = $this->service->collectForAgreement($agreement);

        $this->assertContains('Client insurance document #1', $payload['attachedLabels']);
        $this->assertContains('Client insurance document #2', $payload['attachedLabels']);
        $this->assertNotContains('Company insurance document', $payload['attachedLabels']);
    }

    public function test_missing_documents_are_reported_but_collection_still_returns_attachments(): void
    {
        @unlink(public_path('uploads/driver_licenses/dvla-summary.pdf'));
        $agreement = $this->createAgreement(false, null);

        $payload = $this->service->collectForAgreement($agreement);

        $this->assertNotEmpty($payload['attachments']);
        $this->assertContains('Driving licence summary', $payload['missingDocuments']);
    }

    public function test_send_client_documents_route_sends_email_to_recipient(): void
    {
        Mail::fake();
        $agreement = $this->createAgreement(false, null);

        $response = $this->post(route('agreements.send-client-documents', $agreement));

        $response->assertRedirect();
        Mail::assertSent(AgreementClientDocumentsMail::class, function (AgreementClientDocumentsMail $mail) {
            return $mail->hasTo('docs-driver@example.com')
                && $mail->hasCc('jawad@samoretraders.com')
                && count($mail->attachmentsData) > 0;
        });
    }

    public function test_send_client_documents_blocked_without_driver_email(): void
    {
        Mail::fake();
        $this->driver->update(['email' => null]);
        $agreement = $this->createAgreement(false, null);

        $response = $this->post(route('agreements.send-client-documents', $agreement));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        Mail::assertNothingSent();
    }

    public function test_preview_client_documents_email_returns_404_when_dev_mode_disabled(): void
    {
        config(['app.dev_mode' => false]);
        $agreement = $this->createAgreement(false, null);

        $response = $this->get(route('agreements.preview-client-documents-email', $agreement));

        $response->assertNotFound();
    }

    public function test_preview_client_documents_email_renders_email_when_dev_mode_enabled(): void
    {
        config(['app.dev_mode' => true]);
        $agreement = $this->createAgreement(false, null);

        $response = $this->get(route('agreements.preview-client-documents-email', $agreement));

        $response->assertOk();
        $response->assertSee('DEV MODE');
        $response->assertSee('docs-driver@example.com');
        $response->assertSee('jawad@samoretraders.com');
        $response->assertSee('Client Documents');
        $response->assertSee('Attached Documents');
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
        Schema::table('cars', function (Blueprint $table) {
            $table->json('v5_document')->nullable();
        });
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('driver_license_document')->nullable();
            $table->string('dvla_license_summary')->nullable();
            $table->string('driver_phd_license_document')->nullable();
            $table->string('phd_card_document')->nullable();
        });
        Schema::table('car_mots', function (Blueprint $table) {
            $table->string('document')->nullable();
        });
        Schema::table('car_phvs', function (Blueprint $table) {
            $table->string('document')->nullable();
        });
        Schema::table('car_insurances', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable();
            $table->unsignedBigInteger('insurance_provider_id')->nullable();
            $table->date('start_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('applied_date')->nullable();
            $table->date('canceled_date')->nullable();
            $table->string('insurance_document')->nullable();
            $table->integer('notify_before_expiry')->nullable();
            $table->unsignedBigInteger('status_id')->nullable();
        });
        Schema::create('insurance_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('provider_name')->nullable();
            $table->string('policy_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });

        Schema::create('car_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id');
            $table->date('service_date');
            $table->decimal('amount', 10, 2)->default(0);
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
    }

    /**
     * @param  list<string>|null  $ownInsuranceFiles
     */
    private function createAgreement(bool $usingOwnInsurance, ?array $ownInsuranceFiles): Agreement
    {
        return Agreement::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $this->car->id,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth()->toDateString(),
            'agreed_rent' => 200,
            'rent_interval' => 'Weekly',
            'deposit_amount' => 300,
            'collection_type' => 'weekly',
            'status_id' => $this->agreementStatus->id,
            'using_own_insurance' => $usingOwnInsurance,
            'own_insurance_proof_document' => $ownInsuranceFiles,
        ]);
    }

    private function seedUploadFiles(): void
    {
        File::ensureDirectoryExists(public_path('uploads/driver_licenses'));
        File::ensureDirectoryExists(public_path('uploads/cars/phv_documents'));
        File::ensureDirectoryExists(public_path('uploads/cars/mot_documents'));
        File::ensureDirectoryExists(public_path('uploads/cars/insurance_documents'));
        File::ensureDirectoryExists(public_path('uploads/cars'));
        File::ensureDirectoryExists(public_path('uploads/insurance_documents'));

        File::put(public_path('uploads/driver_licenses/driver-license.pdf'), 'dummy');
        File::put(public_path('uploads/driver_licenses/dvla-summary.pdf'), 'dummy');
        File::put(public_path('uploads/driver_licenses/private-hire-license.pdf'), 'dummy');
        File::put(public_path('uploads/driver_licenses/private-hire-card.pdf'), 'dummy');
        File::put(public_path('uploads/cars/phv_documents/latest-phv.pdf'), 'dummy');
        File::put(public_path('uploads/cars/mot_documents/latest-mot.pdf'), 'dummy');
        File::put(public_path('uploads/cars/insurance_documents/company-insurance.pdf'), 'dummy');
        File::put(public_path('uploads/cars/v5-logbook.pdf'), 'dummy');
        File::put(public_path('uploads/insurance_documents/client-insurance-a.pdf'), 'dummy');
    }
}
