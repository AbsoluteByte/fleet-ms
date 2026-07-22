<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('statuses')
            ->where('type', 'agreement')
            ->where('name', 'Swap')
            ->exists();

        if (! $exists) {
            DB::table('statuses')->insert([
                'name' => 'Swap',
                'type' => 'agreement',
                'color' => '#7367f0',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('statuses')
            ->where('type', 'agreement')
            ->where('name', 'Swap')
            ->delete();
    }
};
