<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('agreement_additional_charges', 'type')) {
            return;
        }

        Schema::table('agreement_additional_charges', function (Blueprint $table) {
            $table->string('type', 50)
                ->default('miscellaneous_charges')
                ->after('agreement_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('agreement_additional_charges', 'type')) {
            return;
        }

        Schema::table('agreement_additional_charges', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
