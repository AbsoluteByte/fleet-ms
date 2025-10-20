<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            [
                'name' => 'ABC Transport Ltd',
                'director_name' => 'John Smith',
                'address_line_1' => '123 Main Street',
                'address_line_2' => 'Business Park',
                'postcode' => 'SW1A 1AA',
                'town' => 'London',
                'county' => 'Greater London',
                'country_id' => 1,
                'phone' => '+44 20 7946 0958',
                'email' => 'info@abctransport.com',
            ],
            [
                'name' => 'XYZ Logistics',
                'director_name' => 'Sarah Johnson',
                'address_line_1' => '456 High Street',
                'address_line_2' => null,
                'postcode' => 'M1 1AA',
                'town' => 'Manchester',
                'county' => 'Greater Manchester',
                'country_id' => 2,
                'phone' => '+44 161 123 4567',
                'email' => 'contact@xyzlogistics.com',
            ],
        ];

        foreach ($companies as $company) {
            Company::create($company);
        }
    }
}
