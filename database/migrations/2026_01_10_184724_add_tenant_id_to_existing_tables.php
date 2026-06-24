<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $tables = [
            'cars' => fn (Blueprint $table) => $table->index(['tenant_id', 'registration']),
            'drivers' => fn (Blueprint $table) => $table->index(['tenant_id', 'email']),
            'agreements' => fn (Blueprint $table) => $table->index('tenant_id'),
            'companies' => fn (Blueprint $table) => $table->index('tenant_id'),
            'car_mots' => fn (Blueprint $table) => $table->index('tenant_id'),
            'car_phvs' => fn (Blueprint $table) => $table->index('tenant_id'),
            'car_road_taxes' => fn (Blueprint $table) => $table->index('tenant_id'),
            'car_insurances' => fn (Blueprint $table) => $table->index('tenant_id'),
            'insurance_providers' => fn (Blueprint $table) => $table->index('tenant_id'),
            'claims' => fn (Blueprint $table) => $table->index('tenant_id'),
            'expenses' => fn (Blueprint $table) => $table->index('tenant_id'),
            'penalties' => fn (Blueprint $table) => $table->index('tenant_id'),
            'agreement_collections' => fn (Blueprint $table) => $table->index('tenant_id'),
        ];

        foreach ($tables as $tableName => $indexCallback) {
            if (Schema::hasColumn($tableName, 'tenant_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($indexCallback) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->onDelete('cascade');
                $indexCallback($table);
            });
        }
    }

    public function down()
    {
        // Remove tenant_id from all tables
        $tables = [
            'cars', 'drivers', 'agreements', 'companies',
            'car_mots', 'car_phvs', 'car_road_taxes', 'car_insurances',
            'insurance_providers', 'claims', 'expenses', 'penalties',
            'agreement_collections',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }
    }
};
