<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Status;

class StatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            // Agreement statuses
            ['name' => 'Active', 'type' => 'agreement', 'color' => '#28a745'],
            ['name' => 'Expired', 'type' => 'agreement', 'color' => '#dc3545'],
            ['name' => 'Pending', 'type' => 'agreement', 'color' => '#ffc107'],
            ['name' => 'Terminated', 'type' => 'agreement', 'color' => '#6c757d'],

            // Claim statuses
            ['name' => 'Open', 'type' => 'claim', 'color' => '#007bff'],
            ['name' => 'In Progress', 'type' => 'claim', 'color' => '#ffc107'],
            ['name' => 'Closed', 'type' => 'claim', 'color' => '#28a745'],
            ['name' => 'Rejected', 'type' => 'claim', 'color' => '#dc3545'],

            // Penalty statuses
            ['name' => 'Unpaid', 'type' => 'penalty', 'color' => '#dc3545'],
            ['name' => 'Paid', 'type' => 'penalty', 'color' => '#28a745'],
            ['name' => 'Disputed', 'type' => 'penalty', 'color' => '#ffc107'],
            ['name' => 'Cancelled', 'type' => 'penalty', 'color' => '#6c757d'],

            // Insurance statuses
            ['name' => 'Active', 'type' => 'insurance', 'color' => '#28a745'],
            ['name' => 'Expired', 'type' => 'insurance', 'color' => '#dc3545'],
            ['name' => 'Pending Renewal', 'type' => 'insurance', 'color' => '#ffc107'],
            ['name' => 'Cancelled', 'type' => 'insurance', 'color' => '#6c757d'],
        ];

        foreach ($statuses as $status) {
            Status::create($status);
        }
    }
}
