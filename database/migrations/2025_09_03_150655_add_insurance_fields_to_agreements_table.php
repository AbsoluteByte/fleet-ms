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
        if (! Schema::hasColumn('agreements', 'insurance_type')) {
            Schema::table('agreements', function (Blueprint $table) {
                $table->string('insurance_type')->nullable()->after('rent_interval');
            });
        }

        if (! Schema::hasColumn('agreements', 'using_own_insurance')) {
            Schema::table('agreements', function (Blueprint $table) {
                $anchor = Schema::hasColumn('agreements', 'insurance_type') ? 'insurance_type' : 'rent_interval';
                $table->boolean('using_own_insurance')->default(false)->after($anchor);
            });
        }

        if (! Schema::hasColumn('agreements', 'insurance_provider_id')) {
            Schema::table('agreements', function (Blueprint $table) {
                $table->unsignedBigInteger('insurance_provider_id')->nullable()->after('using_own_insurance');
            });
        }

        if (! Schema::hasColumn('agreements', 'own_insurance_provider_name')) {
            Schema::table('agreements', function (Blueprint $table) {
                $table->string('own_insurance_provider_name')->nullable()->after('insurance_provider_id');
            });
        }

        if (! Schema::hasColumn('agreements', 'own_insurance_start_date')) {
            Schema::table('agreements', function (Blueprint $table) {
                $table->date('own_insurance_start_date')->nullable()->after('own_insurance_provider_name');
            });
        }

        if (! Schema::hasColumn('agreements', 'own_insurance_end_date')) {
            Schema::table('agreements', function (Blueprint $table) {
                $table->date('own_insurance_end_date')->nullable()->after('own_insurance_start_date');
            });
        }

        if (! Schema::hasColumn('agreements', 'own_insurance_type')) {
            Schema::table('agreements', function (Blueprint $table) {
                $table->string('own_insurance_type')->nullable()->after('own_insurance_end_date');
            });
        }

        if (! Schema::hasColumn('agreements', 'own_insurance_policy_number')) {
            Schema::table('agreements', function (Blueprint $table) {
                $table->string('own_insurance_policy_number')->nullable()->after('own_insurance_type');
            });
        }

        if (! Schema::hasColumn('agreements', 'own_insurance_proof_document')) {
            Schema::table('agreements', function (Blueprint $table) {
                $table->string('own_insurance_proof_document')->nullable()->after('own_insurance_policy_number');
            });
        }

        try {
            Schema::table('agreements', function (Blueprint $table) {
                $table->foreign('insurance_provider_id')->references('id')->on('insurance_providers')->onDelete('set null');
            });
        } catch (\Throwable $e) {
            // Foreign key may already exist.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            if (Schema::hasColumn('agreements', 'insurance_provider_id')) {
                try {
                    $table->dropForeign(['insurance_provider_id']);
                } catch (\Throwable $e) {
                    // Foreign key may already be missing.
                }
            }

            $columns = array_filter([
                Schema::hasColumn('agreements', 'own_insurance_proof_document') ? 'own_insurance_proof_document' : null,
                Schema::hasColumn('agreements', 'own_insurance_policy_number') ? 'own_insurance_policy_number' : null,
                Schema::hasColumn('agreements', 'own_insurance_type') ? 'own_insurance_type' : null,
                Schema::hasColumn('agreements', 'own_insurance_end_date') ? 'own_insurance_end_date' : null,
                Schema::hasColumn('agreements', 'own_insurance_start_date') ? 'own_insurance_start_date' : null,
                Schema::hasColumn('agreements', 'own_insurance_provider_name') ? 'own_insurance_provider_name' : null,
                Schema::hasColumn('agreements', 'insurance_provider_id') ? 'insurance_provider_id' : null,
                Schema::hasColumn('agreements', 'using_own_insurance') ? 'using_own_insurance' : null,
                Schema::hasColumn('agreements', 'insurance_type') ? 'insurance_type' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
