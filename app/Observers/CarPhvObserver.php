<?php

namespace App\Observers;

use App\Models\CarPhv;
use App\Observers\Concerns\SyncsCarFleetCompliance;

class CarPhvObserver
{
    use SyncsCarFleetCompliance;

    public function created(CarPhv $phv): void
    {
        $this->syncCarFleetCompliance($phv->car_id);
    }

    public function updated(CarPhv $phv): void
    {
        $this->syncCarFleetCompliance($phv->car_id);
    }

    public function deleted(CarPhv $phv): void
    {
        $this->syncCarFleetCompliance($phv->car_id);
    }
}
