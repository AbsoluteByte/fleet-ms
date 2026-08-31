<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Car;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Status;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AgreementPdfService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\Concerns\SetupAgreementChangeCarDatabase;
use Tests\TestCase;

class AgreementCompanySignaturePdfTest extends TestCase
{
    use SetupAgreementChangeCarDatabase;

    private const SIGNATURE_PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private const PNG_BYTES = "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x06\x00\x00\x00\x1f\x15\xc4\x89\x00\x00\x00\rIDATx\x9cc\x00\x01\x00\x00\x05\x00\x01\r\n-\xdb\x00\x00\x00\x00IEND\xaeB`\x82";

    private Tenant $tenant;

    private Driver $driver;

    private Status $activeStatus;

    private User $user;

    private string $signatureDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RoleMiddleware::class);
        $this->setUpAgreementChangeCarDatabase();
        $this->setUpHttpTestExtras();
        $this->seedPermissionLetterSignatureFiles();

        Carbon::setTestNow(Carbon::parse('2026-08-19 10:00:00'));

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Company Sig Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $this->driver = Driver::query()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Sig',
            'last_name' => 'Driver',
            'email' => 'sig-driver@example.com',
            'phone_number' => '07000000088',
        ]);

        $this->activeStatus = Status::query()->create(['name' => 'Active', 'type' => 'agreement']);

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
        if (isset($this->signatureDir) && is_dir($this->signatureDir)) {
            File::deleteDirectory($this->signatureDir);
        }

        Carbon::setTestNow();
        Schema::dropIfExists('agreement_signature_tokens');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('car_services');
        Schema::dropIfExists('car_status_histories');
        Schema::dropIfExists('agreement_collections');
        $this->tearDownAgreementChangeCarDatabase();
        parent::tearDown();
    }

    public function test_samore_agreement_pdf_view_data_includes_company_signature(): void
    {
        $agreement = $this->createAgreementForCompany('Samore Traders Ltd');

        $data = app(AgreementPdfService::class)->agreementPdfViewData($agreement);

        $this->assertNotNull($data['letterMeta']['signature_uri'] ?? null);
        $this->assertSame('JAWAD SAMORE', $data['letterMeta']['signatory_name']);
        $this->assertStringContainsString('data:image/png;base64,', (string) $data['letterMeta']['signature_uri']);
    }

    public function test_proactive_agreement_pdf_view_data_includes_company_signature(): void
    {
        $agreement = $this->createAgreementForCompany('Proactive Hybrid Corporate Ltd');

        $data = app(AgreementPdfService::class)->agreementPdfViewData($agreement);

        $this->assertNotNull($data['letterMeta']['signature_uri'] ?? null);
        $this->assertSame('AMNA CHOUDHRY', $data['letterMeta']['signatory_name']);
    }

    public function test_unsigned_agreement_pdf_html_includes_company_signature_image(): void
    {
        $agreement = $this->createAgreementForCompany('Samore Traders Ltd');
        $data = app(AgreementPdfService::class)->agreementPdfViewData($agreement);

        $html = View::make('backend.agreements.agreement_pdf', $data)->render();

        $this->assertStringContainsString((string) $data['letterMeta']['signature_uri'], $html);
        $this->assertStringContainsString('JAWAD SAMORE', $html);
        $this->assertStringContainsString('Company Director', $html);
    }

    public function test_signed_agreement_pdf_html_includes_both_client_and_company_signatures(): void
    {
        $agreement = $this->createAgreementForCompany('Samore Traders Ltd');
        $data = app(AgreementPdfService::class)->agreementPdfViewData($agreement);
        $data['signature_image'] = self::SIGNATURE_PNG;

        $html = View::make('backend.agreements.agreement_pdf_signed', $data)->render();

        $this->assertStringContainsString(self::SIGNATURE_PNG, $html);
        $this->assertStringContainsString((string) $data['letterMeta']['signature_uri'], $html);
        $this->assertStringContainsString('Client', $html);
        $this->assertStringContainsString('JAWAD SAMORE', $html);
    }

    private function createAgreementForCompany(string $companyName): Agreement
    {
        $company = Company::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => $companyName,
            'director_name' => 'Director Name',
        ]);

        $carModelId = DB::table('car_models')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'name' => 'Sig Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $counselId = DB::table('counsels')->insertGetId([
            'name' => 'Sig Counsel',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $car = Car::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $company->id,
            'car_model_id' => $carModelId,
            'registration' => 'SIG'.uniqid(),
            'color' => 'Black',
            'vin' => uniqid('VIN', true),
            'manufacture_year' => 2020,
            'registration_year' => 2020,
            'purchase_date' => '2020-01-01',
            'purchase_price' => 10000,
            'purchase_type' => 'uk',
            'fleet_status' => 'rented',
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

        return Agreement::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $company->id,
            'driver_id' => $this->driver->id,
            'car_id' => $car->id,
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

    private function seedPermissionLetterSignatureFiles(): void
    {
        $this->signatureDir = public_path('uploads/companies/permission-letters');
        File::ensureDirectoryExists($this->signatureDir);

        foreach (['samore-signature.png', 'proactive-signature.png'] as $filename) {
            file_put_contents($this->signatureDir.'/'.$filename, self::PNG_BYTES);
        }
    }

    private function setUpHttpTestExtras(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'status')) {
                $table->unsignedTinyInteger('status')->default(1);
            }
        });

        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'director_name')) {
                $table->string('director_name')->nullable();
            }
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
    }
}
