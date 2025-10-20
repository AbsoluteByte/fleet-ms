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
        Schema::table('agreements', function (Blueprint $table) {
            // Add insurance choice field
            $table->boolean('using_own_insurance')->default(false)->after('insurance_type');

            // Fields for external insurance provider
            $table->unsignedBigInteger('insurance_provider_id')->nullable()->after('using_own_insurance');

            // Fields for own insurance
            $table->string('own_insurance_provider_name')->nullable()->after('insurance_provider_id');
            $table->date('own_insurance_start_date')->nullable()->after('own_insurance_provider_name');
            $table->date('own_insurance_end_date')->nullable()->after('own_insurance_start_date');
            $table->string('own_insurance_type')->nullable()->after('own_insurance_end_date');
            $table->string('own_insurance_policy_number')->nullable()->after('own_insurance_type');
            $table->string('own_insurance_proof_document')->nullable()->after('own_insurance_policy_number');

            // Foreign key constraint
            $table->foreign('insurance_provider_id')->references('id')->on('insurance_providers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropForeign(['insurance_provider_id']);
            $table->dropColumn([
                'using_own_insurance',
                'insurance_provider_id',
                'own_insurance_provider_name',
                'own_insurance_start_date',
                'own_insurance_end_date',
                'own_insurance_type',
                'own_insurance_policy_number',
                'own_insurance_proof_document'
            ]);
        });
    }
};
