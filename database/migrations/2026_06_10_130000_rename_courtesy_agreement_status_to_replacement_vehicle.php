<?php

use App\Models\Status;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Status::query()
            ->where('type', 'agreement')
            ->where('name', 'Courtesy')
            ->update(['name' => 'Replacement Vehicle']);
    }

    public function down(): void
    {
        Status::query()
            ->where('type', 'agreement')
            ->where('name', 'Replacement Vehicle')
            ->update(['name' => 'Courtesy']);
    }
};
