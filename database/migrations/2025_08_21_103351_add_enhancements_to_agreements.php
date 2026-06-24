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
        if (! Schema::hasColumn('agreements', 'security_deposit')) {
            Schema::table('agreements', function (Blueprint $table) {
                $table->decimal('security_deposit', 10, 2)->nullable()->after('deposit_amount');
            });
        }

        if (! Schema::hasColumn('agreements', 'mileage_out')) {
            Schema::table('agreements', function (Blueprint $table) {
                $anchor = Schema::hasColumn('agreements', 'security_deposit') ? 'security_deposit' : 'deposit_amount';
                $table->integer('mileage_out')->after($anchor)->nullable();
            });
        }

        if (! Schema::hasColumn('agreements', 'mileage_in')) {
            Schema::table('agreements', function (Blueprint $table) {
                $table->integer('mileage_in')->after('mileage_out')->nullable();
            });
        }

        if (! Schema::hasColumn('agreements', 'collection_type')) {
            Schema::table('agreements', function (Blueprint $table) {
                $table->enum('collection_type', ['weekly', 'monthly', 'static'])->after('rent_interval')->default('monthly');
            });
        }

        if (! Schema::hasColumn('agreements', 'auto_schedule_collections')) {
            Schema::table('agreements', function (Blueprint $table) {
                $table->boolean('auto_schedule_collections')->after('collection_type')->default(true);
            });
        }

        if (! Schema::hasColumn('agreements', 'next_collection_date')) {
            Schema::table('agreements', function (Blueprint $table) {
                $table->date('next_collection_date')->after('auto_schedule_collections')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('agreements', 'next_collection_date') ? 'next_collection_date' : null,
                Schema::hasColumn('agreements', 'auto_schedule_collections') ? 'auto_schedule_collections' : null,
                Schema::hasColumn('agreements', 'collection_type') ? 'collection_type' : null,
                Schema::hasColumn('agreements', 'mileage_in') ? 'mileage_in' : null,
                Schema::hasColumn('agreements', 'mileage_out') ? 'mileage_out' : null,
                Schema::hasColumn('agreements', 'security_deposit') ? 'security_deposit' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
