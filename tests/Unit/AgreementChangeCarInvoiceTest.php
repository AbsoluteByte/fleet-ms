<?php

namespace Tests\Unit;

use App\Models\Agreement;
use App\Services\AgreementInvoiceService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AgreementChangeCarInvoiceTest extends TestCase
{
    private AgreementInvoiceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AgreementInvoiceService;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_calculate_change_car_adjustment_returns_positive_for_rent_increase_mid_period(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-18 10:00:00')); // Wednesday

        $old = $this->makeAgreement([
            'agreed_rent' => 200,
            'rent_interval' => 'weekly',
            'start_date' => Carbon::parse('2026-06-17'), // Tuesday anchor
        ]);

        $new = $this->makeAgreement([
            'agreed_rent' => 300,
            'rent_interval' => 'weekly',
            'start_date' => Carbon::parse('2026-06-18'),
        ]);

        $adjustment = $this->service->calculateChangeCarAdjustment($new, $old);

        $this->assertGreaterThan(0, $adjustment);
        $this->assertSame('invoice', $this->service->changeCarAdjustmentType($adjustment));
    }

    public function test_calculate_change_car_adjustment_returns_negative_for_rent_decrease_mid_period(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-18 10:00:00'));

        $old = $this->makeAgreement([
            'agreed_rent' => 300,
            'rent_interval' => 'weekly',
            'start_date' => Carbon::parse('2026-06-17'),
        ]);

        $new = $this->makeAgreement([
            'agreed_rent' => 200,
            'rent_interval' => 'weekly',
            'start_date' => Carbon::parse('2026-06-18'),
        ]);

        $adjustment = $this->service->calculateChangeCarAdjustment($new, $old);

        $this->assertLessThan(0, $adjustment);
        $this->assertSame('credit', $this->service->changeCarAdjustmentType($adjustment));
    }

    public function test_calculate_change_car_adjustment_returns_zero_for_same_rent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-18 10:00:00'));

        $old = $this->makeAgreement([
            'agreed_rent' => 250,
            'rent_interval' => 'weekly',
            'start_date' => Carbon::parse('2026-06-17'),
        ]);

        $new = $this->makeAgreement([
            'agreed_rent' => 250,
            'rent_interval' => 'weekly',
            'start_date' => Carbon::parse('2026-06-18'),
        ]);

        $adjustment = $this->service->calculateChangeCarAdjustment($new, $old);

        $this->assertSame(0.0, $adjustment);
        $this->assertSame('none', $this->service->changeCarAdjustmentType($adjustment));
    }

    public function test_calculate_change_car_adjustment_returns_zero_on_billing_anchor_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-24 10:00:00')); // Next Tuesday

        $old = $this->makeAgreement([
            'agreed_rent' => 200,
            'rent_interval' => 'weekly',
            'start_date' => Carbon::parse('2026-06-17'),
        ]);

        $new = $this->makeAgreement([
            'agreed_rent' => 300,
            'rent_interval' => 'weekly',
            'start_date' => Carbon::parse('2026-06-24'),
        ]);

        $adjustment = $this->service->calculateChangeCarAdjustment($new, $old, Carbon::parse('2026-06-24'));

        $this->assertSame(0.0, $adjustment);
        $this->assertSame('none', $this->service->changeCarAdjustmentType($adjustment));
    }

    public function test_next_billing_anchor_uses_old_agreement_start_date(): void
    {
        $originalStart = Carbon::parse('2026-06-17'); // Tuesday
        $changeDate = Carbon::parse('2026-06-18'); // Wednesday

        $nextAnchor = $this->service->nextBillingAnchor($originalStart, $changeDate, 'weekly');

        $this->assertTrue($nextAnchor->eq(Carbon::parse('2026-06-24')));
    }

    #[DataProvider('adjustmentTypeProvider')]
    public function test_change_car_adjustment_type(float $amount, string $expected): void
    {
        $this->assertSame($expected, $this->service->changeCarAdjustmentType($amount));
    }

    public static function adjustmentTypeProvider(): array
    {
        return [
            'positive' => [12.50, 'invoice'],
            'negative' => [-8.25, 'credit'],
            'zero' => [0.0, 'none'],
        ];
    }

    public function test_calculate_upgrade_proration_only_returns_positive_values(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-18 10:00:00'));

        $old = $this->makeAgreement([
            'agreed_rent' => 300,
            'rent_interval' => 'weekly',
            'start_date' => Carbon::parse('2026-06-17'),
        ]);

        $new = $this->makeAgreement([
            'agreed_rent' => 200,
            'rent_interval' => 'weekly',
            'start_date' => Carbon::parse('2026-06-18'),
        ]);

        $this->assertSame(0.0, $this->service->calculateUpgradeProration($new, $old));
    }

    private function makeAgreement(array $attributes): Agreement
    {
        return new Agreement(array_merge([
            'discount_type' => null,
            'discount_value' => null,
            'end_date' => Carbon::parse('2027-06-17'),
        ], $attributes));
    }
}
