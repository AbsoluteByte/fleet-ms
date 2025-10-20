<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CarModel;

class CarModelSeeder extends Seeder
{
    public function run(): void
    {
        $models = [
            'Toyota Prius',
            'Honda Civic',
            'Ford Focus',
            'Volkswagen Golf',
            'BMW 3 Series',
            'Mercedes C-Class',
            'Audi A4',
            'Nissan Qashqai',
            'Hyundai i30',
            'Kia Sportage',
            'Skoda Octavia',
            'Vauxhall Astra',
            'Peugeot 308',
            'Renault Megane',
            'Seat Leon',
        ];

        foreach ($models as $model) {
            CarModel::create(['name' => $model]);
        }
    }
}
