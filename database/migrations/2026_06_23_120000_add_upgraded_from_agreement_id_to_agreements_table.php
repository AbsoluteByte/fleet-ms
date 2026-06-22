<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->foreignId('upgraded_from_agreement_id')
                ->nullable()
                ->after('parent_agreement_id')
                ->constrained('agreements')
                ->nullOnDelete();

            $table->index('upgraded_from_agreement_id');
        });
    }

    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropForeign(['upgraded_from_agreement_id']);
            $table->dropColumn('upgraded_from_agreement_id');
        });
    }
};
