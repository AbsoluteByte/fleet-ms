<?php

namespace Tests\Unit;

use App\Models\Agreement;
use App\Models\Car;
use App\Models\Status;
use PHPUnit\Framework\TestCase;

class AgreementPreviousVehicleRegistrationTest extends TestCase
{
    public function test_returns_parent_car_registration_for_replacement_vehicle_agreement(): void
    {
        $parentCar = new Car(['registration' => 'OLD123']);
        $parentAgreement = new Agreement;
        $parentAgreement->setRelation('car', $parentCar);

        $status = new Status(['name' => 'Replacement Vehicle', 'type' => 'agreement']);
        $agreement = new Agreement;
        $agreement->setRelation('status', $status);
        $agreement->setRelation('parentAgreement', $parentAgreement);

        $this->assertSame('OLD123', $agreement->previousVehicleRegistration());
    }

    public function test_returns_upgraded_from_car_registration_for_swap_agreement(): void
    {
        $oldCar = new Car(['registration' => 'SWP999']);
        $oldAgreement = new Agreement;
        $oldAgreement->setRelation('car', $oldCar);

        $agreement = new Agreement(['upgraded_from_agreement_id' => 1]);
        $agreement->setRelation('status', new Status(['name' => 'Active', 'type' => 'agreement']));
        $agreement->setRelation('upgradedFromAgreement', $oldAgreement);

        $this->assertSame('SWP999', $agreement->previousVehicleRegistration());
    }

    public function test_returns_null_for_standard_active_agreement(): void
    {
        $status = new Status(['name' => 'Active', 'type' => 'agreement']);
        $agreement = new Agreement;
        $agreement->setRelation('status', $status);

        $this->assertNull($agreement->previousVehicleRegistration());
    }
}
