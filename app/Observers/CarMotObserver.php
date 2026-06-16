<?php

namespace App\Observers;

use App\Models\CarMot;
use App\Observers\Concerns\SyncsCarFleetCompliance;

class CarMotObserver
{
    use SyncsCarFleetCompliance;

    public function created(CarMot $mot): void
    {
        $this->syncCarFleetCompliance($mot->car_id);
    }

    public function updated(CarMot $mot): void
    {
        $this->syncCarFleetCompliance($mot->car_id);
    }

    public function deleted(CarMot $mot): void
    {
        $this->syncCarFleetCompliance($mot->car_id);
    }
}
