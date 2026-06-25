<?php

namespace Tests\Unit;

use App\Models\Car;
use App\Models\CarMot;
use App\Models\Company;
use App\Models\Tenant;
use App\Services\MotTestDateImportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\SetupPhvlManagementDatabase;
use Tests\TestCase;

class MotTestDateImportServiceTest extends TestCase
{
    use SetupPhvlManagementDatabase;

    private int $tenantId;

    private int $companyId;

    private int $carModelId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPhvlManagementDatabase();

        $tenant = Tenant::query()->create([
            'company_name' => 'MOT Import Tenant',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $company = Company::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'MOT Import Company',
        ]);

        $this->tenantId = $tenant->id;
        $this->companyId = $company->id;
        $this->carModelId = (int) DB::table('car_models')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => 'Test Model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('car_road_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id');
            $table->date('start_date')->nullable();
            $table->timestamps();
        });
    }

    public function test_import_updates_latest_mot_test_date_by_registration(): void
    {
        $car = Car::query()->create([
            'tenant_id' => $this->tenantId,
            'company_id' => $this->companyId,
            'car_model_id' => $this->carModelId,
            'registration' => 'LX66 ETO',
        ]);

        CarMot::withoutEvents(function () use ($car, &$olderMot, &$latestMot) {
            $olderMot = CarMot::query()->create([
                'car_id' => $car->id,
                'test_date' => '2024-04-28',
                'expiry_date' => '2025-04-28',
            ]);

            $latestMot = CarMot::query()->create([
                'car_id' => $car->id,
                'test_date' => null,
                'expiry_date' => '2026-04-28',
            ]);
        });

        $csvPath = storage_path('app/testing-mot-test-date.csv');
        file_put_contents($csvPath, implode("\n", [
            'CAR REG,MOT TEST DATE,MOT EXPIRY DATE',
            'LX66 ETO,28.04.2025,28.04.2026',
        ]));

        $report = app(MotTestDateImportService::class)->import($csvPath, $this->tenantId);

        @unlink($csvPath);

        $this->assertSame(1, $report['summary']['success']);
        $this->assertSame('success', $report['rows'][0]['status']);
        $this->assertSame('LX66 ETO', $report['rows'][0]['registration']);

        $latestMot->refresh();
        $olderMot->refresh();

        $this->assertSame('2025-04-28', $latestMot->test_date?->format('Y-m-d'));
        $this->assertSame('2024-04-28', $olderMot->test_date?->format('Y-m-d'));
    }

    public function test_import_skips_when_car_is_not_found(): void
    {
        $csvPath = storage_path('app/testing-mot-test-date-missing.csv');
        file_put_contents($csvPath, implode("\n", [
            'CAR REG,MOT TEST DATE,MOT EXPIRY DATE',
            'UNKNOWN1,28.04.2025,28.04.2026',
        ]));

        $report = app(MotTestDateImportService::class)->import($csvPath, $this->tenantId);

        @unlink($csvPath);

        $this->assertSame(1, $report['summary']['skipped']);
        $this->assertSame('skipped', $report['rows'][0]['status']);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('car_road_taxes');
        $this->tearDownPhvlManagementDatabase();

        parent::tearDown();
    }
}
