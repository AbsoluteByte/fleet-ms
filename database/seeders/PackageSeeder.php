<?php
// database/seeders/PackageSeeder.php
namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run()
    {

        $packages = [
            // ✅ Add Trial Package (Free, Limited Features)
            [
                'name' => 'Free Trial',
                'description' => 'Try all features free for 30 days',
                'billing_period' => 'monthly',
                'price' => 0.00,
                'max_users' => 3,
                'max_vehicles' => 5,
                'max_drivers' => 5,
                'has_notifications' => true,
                'has_reports' => false,
                'has_api_access' => false,
                'trial_days' => 30,
                'is_active' => true,
                'features' => [
                    'Full System Access',
                    'All Features Unlocked',
                    '30 Days Free Trial',
                    'No Credit Card Required'
                ]
            ],

            // MONTHLY PACKAGES
            [
                'name' => 'Basic Monthly',
                'description' => 'Perfect for small businesses starting out',
                'billing_period' => 'monthly',
                'price' => 29.99,
                'max_users' => 3,
                'max_vehicles' => 5,
                'max_drivers' => 5,
                'has_notifications' => true,
                'has_reports' => false,
                'has_api_access' => false,
                'trial_days' => 30,
                'is_active' => true,
                'features' => [
                    'Fleet Management',
                    'Driver Management',
                    'Basic Reports',
                    'Email Notifications'
                ]
            ],
            [
                'name' => 'Standard Monthly',
                'description' => 'Great for growing businesses',
                'billing_period' => 'monthly',
                'price' => 59.99,
                'max_users' => 10,
                'max_vehicles' => 20,
                'max_drivers' => 20,
                'has_notifications' => true,
                'has_reports' => true,
                'has_api_access' => false,
                'trial_days' => 30,
                'is_active' => true,
                'features' => [
                    'Everything in Basic',
                    'Advanced Reports',
                    'SMS Notifications',
                    'Custom Branding',
                    'Priority Support'
                ]
            ],
            [
                'name' => 'Premium Monthly',
                'description' => 'For large enterprises',
                'billing_period' => 'monthly',
                'price' => 99.99,
                'max_users' => -1,
                'max_vehicles' => -1,
                'max_drivers' => -1,
                'has_notifications' => true,
                'has_reports' => true,
                'has_api_access' => true,
                'trial_days' => 30,
                'is_active' => true,
                'features' => [
                    'Everything in Standard',
                    'Unlimited Users',
                    'Unlimited Vehicles',
                    'Unlimited Drivers',
                    'API Access',
                    'Custom Integrations',
                    'Dedicated Account Manager',
                    '24/7 Priority Support'
                ]
            ],

            // QUARTERLY PACKAGES
            [
                'name' => 'Basic Quarterly',
                'description' => 'Save 10% with quarterly billing',
                'billing_period' => 'quarterly',
                'price' => 80.97, // 29.99 * 3 * 0.9
                'max_users' => 3,
                'max_vehicles' => 5,
                'max_drivers' => 5,
                'has_notifications' => true,
                'has_reports' => false,
                'has_api_access' => false,
                'trial_days' => 30,
                'is_active' => true,
                'features' => [
                    'Fleet Management',
                    'Driver Management',
                    'Basic Reports',
                    'Email Notifications',
                    'Save 10%'
                ]
            ],
            [
                'name' => 'Standard Quarterly',
                'description' => 'Save 10% with quarterly billing',
                'billing_period' => 'quarterly',
                'price' => 161.97, // 59.99 * 3 * 0.9
                'max_users' => 10,
                'max_vehicles' => 20,
                'max_drivers' => 20,
                'has_notifications' => true,
                'has_reports' => true,
                'has_api_access' => false,
                'trial_days' => 30,
                'is_active' => true,
                'features' => [
                    'Everything in Basic',
                    'Advanced Reports',
                    'SMS Notifications',
                    'Custom Branding',
                    'Priority Support',
                    'Save 10%'
                ]
            ],
            [
                'name' => 'Premium Quarterly',
                'description' => 'Save 10% with quarterly billing',
                'billing_period' => 'quarterly',
                'price' => 269.97, // 99.99 * 3 * 0.9
                'max_users' => -1,
                'max_vehicles' => -1,
                'max_drivers' => -1,
                'has_notifications' => true,
                'has_reports' => true,
                'has_api_access' => true,
                'trial_days' => 30,
                'is_active' => true,
                'features' => [
                    'Everything in Standard',
                    'Unlimited Resources',
                    'API Access',
                    'Custom Integrations',
                    'Dedicated Account Manager',
                    '24/7 Priority Support',
                    'Save 10%'
                ]
            ],

            // YEARLY PACKAGES
            [
                'name' => 'Basic Yearly',
                'description' => 'Save 20% with yearly billing',
                'billing_period' => 'yearly',
                'price' => 287.90, // 29.99 * 12 * 0.8
                'max_users' => 3,
                'max_vehicles' => 5,
                'max_drivers' => 5,
                'has_notifications' => true,
                'has_reports' => false,
                'has_api_access' => false,
                'trial_days' => 30,
                'is_active' => true,
                'features' => [
                    'Fleet Management',
                    'Driver Management',
                    'Basic Reports',
                    'Email Notifications',
                    'Save 20%'
                ]
            ],
            [
                'name' => 'Standard Yearly',
                'description' => 'Save 20% with yearly billing',
                'billing_period' => 'yearly',
                'price' => 575.90, // 59.99 * 12 * 0.8
                'max_users' => 10,
                'max_vehicles' => 20,
                'max_drivers' => 20,
                'has_notifications' => true,
                'has_reports' => true,
                'has_api_access' => false,
                'trial_days' => 30,
                'is_active' => true,
                'features' => [
                    'Everything in Basic',
                    'Advanced Reports',
                    'SMS Notifications',
                    'Custom Branding',
                    'Priority Support',
                    'Save 20%'
                ]
            ],
            [
                'name' => 'Premium Yearly',
                'description' => 'Save 20% with yearly billing',
                'billing_period' => 'yearly',
                'price' => 959.90, // 99.99 * 12 * 0.8
                'max_users' => -1,
                'max_vehicles' => -1,
                'max_drivers' => -1,
                'has_notifications' => true,
                'has_reports' => true,
                'has_api_access' => true,
                'trial_days' => 30,
                'is_active' => true,
                'features' => [
                    'Everything in Standard',
                    'Unlimited Resources',
                    'API Access',
                    'Custom Integrations',
                    'Dedicated Account Manager',
                    '24/7 Priority Support',
                    'Save 20%'
                ]
            ],
        ];

        foreach ($packages as $package) {
            Package::create($package);
        }
    }
}
