<?php

namespace Tests\Unit;

use App\Models\Car;
use PHPUnit\Framework\TestCase;

class CarComplianceNotificationExclusionsTest extends TestCase
{
    public function test_compliance_notification_exclusions_cover_terminal_fleet_statuses(): void
    {
        $statuses = Car::fleetStatusesExcludedFromComplianceNotifications();

        $this->assertContains('damaged', $statuses);
        $this->assertContains('stolen', $statuses);
        $this->assertContains('for_sale', $statuses);
        $this->assertContains('written_off', $statuses);
        $this->assertContains('sold', $statuses);
    }
}
