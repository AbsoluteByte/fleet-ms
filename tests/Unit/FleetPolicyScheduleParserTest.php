<?php

namespace Tests\Unit;

use App\Services\FleetPolicyScheduleParser;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class FleetPolicyScheduleParserTest extends TestCase
{
    public function test_parse_text_extracts_header_and_vehicle_rows(): void
    {
        $text = <<<'TXT'
POLICY SCHEDULE
Policy NumberMTFL0450052042	InsuredSamore Traders Ltd
Inception24/06/2026	Address22 Archibald Road
Expiry21/07/2026	Gross Premium£79,415.00
Date Added Vehicle Make & Model GVW Vehicle Reg Cover Annual Vehicle Rate
24/06/2026 TOYOTA PRIUS	0T	BR68VXW	Comprehensive	£295
24/06/2026 TOYOTA LAND CRUISER 0T	BX24KCC	Comprehensive	£325
24/06/2026 TOYOTA PRIUS	0T	BR68VXW	Comprehensive	£295
TXT;

        $parsed = (new FleetPolicyScheduleParser)->parseText($text);

        $this->assertSame('2026-06-24', $parsed['inception']?->toDateString());
        $this->assertSame('2026-07-21', $parsed['expiry']?->toDateString());
        $this->assertSame('MTFL0450052042', $parsed['policy_number']);
        $this->assertCount(3, $parsed['vehicles']);
        $this->assertSame('BR68VXW', $parsed['vehicles'][0]['registration']);
        $this->assertSame('BX24KCC', $parsed['vehicles'][1]['registration']);
        $this->assertSame(295.0, $parsed['vehicles'][0]['annual_rate']);
        $this->assertSame('Comprehensive', $parsed['vehicles'][0]['cover']);
    }

    public function test_normalize_registration_strips_spaces(): void
    {
        $parser = new FleetPolicyScheduleParser;

        $this->assertSame('BR68VXW', $parser->normalizeRegistration('BR68 VXW'));
        $this->assertSame('2026-06-24', Carbon::parse('2026-06-24')->toDateString());
    }
}
