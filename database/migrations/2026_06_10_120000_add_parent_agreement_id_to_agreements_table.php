<?php

use App\Models\Status;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->foreignId('parent_agreement_id')
                ->nullable()
                ->after('status_id')
                ->constrained('agreements')
                ->nullOnDelete();

            $table->index('parent_agreement_id');
        });

        Status::firstOrCreate(
            ['name' => 'Replacement Vehicle', 'type' => 'agreement'],
            ['color' => '#17a2b8']
        );
    }

    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropForeign(['parent_agreement_id']);
            $table->dropColumn('parent_agreement_id');
        });

        Status::query()
            ->where('type', 'agreement')
            ->where('name', 'Replacement Vehicle')
            ->delete();
    }
};
