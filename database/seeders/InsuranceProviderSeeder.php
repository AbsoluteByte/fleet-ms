<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InsuranceProvider;
use App\Models\Status;

class InsuranceProviderSeeder extends Seeder
{
    public function run(): void
    {
        $activeStatus = Status::where('type', 'insurance')->where('name', 'Active')->first();

        $providers = [
            [
                'provider_name' => 'Aviva',
                'insurance_type' => 'Comprehensive',
                'amount' => 1200.00,
                'policy_number' => 'AVI123456789',
                'expiry_date' => now()->addMonths(6),
                'status_id' => $activeStatus->id,
            ],
            [
                'provider_name' => 'Admiral',
                'insurance_type' => 'Third Party',
                'amount' => 800.00,
                'policy_number' => 'ADM987654321',
                'expiry_date' => now()->addMonths(8),
                'status_id' => $activeStatus->id,
            ],
            [
                'provider_name' => 'Direct Line',
                'insurance_type' => 'Comprehensive',
                'amount' => 1500.00,
                'policy_number' => 'DL555666777',
                'expiry_date' => now()->addMonths(4),
                'status_id' => $activeStatus->id,
            ],
        ];

        foreach ($providers as $provider) {
            InsuranceProvider::create($provider);
        }
    }
}
