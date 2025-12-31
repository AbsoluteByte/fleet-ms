<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')
                ->after('id')
                ->default(1);

            $table->unsignedBigInteger('createdBy')->nullable();
            $table->unsignedBigInteger('updatedBy')->nullable();
        });

        Schema::table('cars', function (Blueprint $table) {
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreign('createdBy')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('updatedBy')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['createdBy']);
            $table->dropForeign(['updatedBy']);

            $table->dropColumn(['tenant_id', 'createdBy', 'updatedBy']);
        });
    }
};
