<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Car;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\Status;
use App\Models\Tenant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\SetupAgreementChangeCarDatabase;
use Tests\TestCase;

class InvoicePayingCompanyNameTest extends TestCase
{
    use SetupAgreementChangeCarDatabase;

    private Tenant $tenant;

    private Company $company;

    private Driver $driver;

    private Car $car;

    private Status $activeStatus;

    private Agreement $agreement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAgreementChangeCarDatabase();
        $this->setUpHttpTestExtras();

        $this->tenant = Tenant::query()->create([
            'company_name' => 'Invoice Paying Company Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $this->company = Company::query()->create(['tenant_id' => $this->tenant->id, 'name' => 'Fleet Co']);
        $this->driver = Driver::query()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Pay',
            'last_name' => 'Driver',
            'email' => 'pay-driver@example.com',
            'phone_number' => '07000000008',
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

        $this->car = Car::query()->create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'car_model_id' => $carModelId,
            'registration' => 'PC123',
            'color' => 'Black',
            'vin' => 'VINPC123',
            'manufacture_year' => 2020,
            'registration_year' => 2020,
            'purchase_date' => '2020-01-01',
            'purchase_price' => 10000,
            'purchase_type' => 'uk',
            'fleet_status' => Car::FLEET_STATUS_AVAILABLE_FOR_RENT,
        ]);

        $this->activeStatus = Status::query()->create(['name' => 'Active', 'type' => 'agreement']);

        $this->agreement = Agreement::query()->create([
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
            'paying_company_name' => 'ABC Transport Ltd',
        ]);
    }

    protected function tearDown(): void
    {
        $this->tearDownAgreementChangeCarDatabase();

        parent::tearDown();
    }

    public function test_paying_company_name_label_for_agreement_invoice(): void
    {
        $invoice = Invoice::query()->create([
            'driver_id' => $this->driver->id,
            'source_id' => $this->agreement->id,
            'invoice_type' => 'agreement',
            'invoice_date' => '2026-07-01',
            'due_date' => '2026-07-08',
            'subtotal' => 150,
            'total_amount' => 150,
            'paid_amount' => 0,
            'balance_amount' => 150,
            'status' => 'pending',
        ]);

        $invoice->load('sourceAgreement');

        $this->assertSame('ABC Transport Ltd', $invoice->payingCompanyNameLabel());
    }

    public function test_paying_company_name_label_returns_null_when_not_set(): void
    {
        $this->agreement->update(['paying_company_name' => null]);

        $invoice = Invoice::query()->create([
            'driver_id' => $this->driver->id,
            'source_id' => $this->agreement->id,
            'invoice_type' => 'agreement_deposit',
            'invoice_date' => '2026-07-01',
            'due_date' => '2026-07-08',
            'subtotal' => 200,
            'total_amount' => 200,
            'paid_amount' => 0,
            'balance_amount' => 200,
            'status' => 'pending',
        ]);

        $invoice->load('sourceAgreement');

        $this->assertNull($invoice->payingCompanyNameLabel());
    }

    public function test_paying_company_name_label_returns_null_for_non_agreement_invoice(): void
    {
        $invoice = Invoice::query()->create([
            'driver_id' => $this->driver->id,
            'source_id' => null,
            'invoice_type' => 'manual',
            'invoice_date' => '2026-07-01',
            'due_date' => '2026-07-08',
            'subtotal' => 50,
            'total_amount' => 50,
            'paid_amount' => 0,
            'balance_amount' => 50,
            'status' => 'pending',
        ]);

        $this->assertNull($invoice->payingCompanyNameLabel());
    }

    private function setUpHttpTestExtras(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->default(1);
        });

        Schema::table('agreements', function (Blueprint $table) {
            $table->string('paying_company_name')->nullable();
        });
    }
}
