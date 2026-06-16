<?php

namespace App\Observers;

use App\Models\CarRoadTax;
use App\Observers\Concerns\SyncsCarFleetCompliance;

class CarRoadTaxObserver
{
    use SyncsCarFleetCompliance;

    public function created(CarRoadTax $roadTax): void
    {
        $this->syncCarFleetCompliance($roadTax->car_id);
    }

    public function updated(CarRoadTax $roadTax): void
    {
        $this->syncCarFleetCompliance($roadTax->car_id);
    }

    public function deleted(CarRoadTax $roadTax): void
    {
        $this->syncCarFleetCompliance($roadTax->car_id);
    }
}
