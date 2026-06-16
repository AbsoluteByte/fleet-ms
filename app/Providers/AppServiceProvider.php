<?php

namespace App\Providers;

use App\Models\CarMot;
use App\Models\CarPhv;
use App\Models\CarReservation;
use App\Models\CarRoadTax;
use App\Models\VehicleSwap;
use App\Observers\CarMotObserver;
use App\Observers\CarPhvObserver;
use App\Observers\CarRoadTaxObserver;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::model('reservation', CarReservation::class);
        Route::model('vehicle_swap', VehicleSwap::class);

        CarMot::observe(CarMotObserver::class);
        CarRoadTax::observe(CarRoadTaxObserver::class);
        CarPhv::observe(CarPhvObserver::class);
    }
}
