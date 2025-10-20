<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Counsel;

class CounselSeeder extends Seeder
{
    public function run(): void
    {
        $counsels = [
            'Transport for London (TfL)',
            'Birmingham City Council',
            'Manchester City Council',
            'Leeds City Council',
            'Liverpool City Council',
            'Sheffield City Council',
            'Bristol City Council',
            'Newcastle City Council',
            'Nottingham City Council',
            'Leicester City Council',
        ];

        foreach ($counsels as $counsel) {
            Counsel::create(['name' => $counsel]);
        }
    }
}
