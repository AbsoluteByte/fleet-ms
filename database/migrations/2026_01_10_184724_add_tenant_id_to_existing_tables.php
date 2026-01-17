<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add tenant_id to cars table
        Schema::table('cars', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->index(['tenant_id', 'registration']);
        });

        // Add tenant_id to drivers table
        Schema::table('drivers', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->index(['tenant_id', 'email']);
        });

        // Add tenant_id to agreements table
        Schema::table('agreements', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->index('tenant_id');
        });

        // Add tenant_id to companies table
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->index('tenant_id');
        });

        // Add tenant_id to car_mots table
        Schema::table('car_mots', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->index('tenant_id');
        });

        // Add tenant_id to car_phvs table
        Schema::table('car_phvs', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->index('tenant_id');
        });

        // Add tenant_id to car_road_taxes table
        Schema::table('car_road_taxes', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->index('tenant_id');
        });

        // Add tenant_id to car_insurances table
        Schema::table('car_insurances', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->index('tenant_id');
        });

        // Add tenant_id to insurance_providers table
        Schema::table('insurance_providers', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->index('tenant_id');
        });

        // Add tenant_id to claims table
        Schema::table('claims', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->index('tenant_id');
        });

        // Add tenant_id to expenses table
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->index('tenant_id');
        });

        // Add tenant_id to penalties table
        Schema::table('penalties', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->index('tenant_id');
        });

        // Add tenant_id to agreement_collections table
        Schema::table('agreement_collections', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->index('tenant_id');
        });
    }

    public function down()
    {
        // Remove tenant_id from all tables
        $tables = [
            'cars', 'drivers', 'agreements', 'companies',
            'car_mots', 'car_phvs', 'car_road_taxes', 'car_insurances',
            'insurance_providers', 'claims', 'expenses', 'penalties',
            'agreement_collections'
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }
    }
};
