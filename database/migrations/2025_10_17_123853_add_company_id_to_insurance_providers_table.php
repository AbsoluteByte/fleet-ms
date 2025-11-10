<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('insurance_providers', function (Blueprint $table) {
            if (!Schema::hasColumn('insurance_providers', 'company_id')) {
                $table->foreignId('company_id')->after('id')->constrained()->onDelete('cascade');
            } else {
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            }
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_providers', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
