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
        Schema::table('counsels', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('createdBy')->nullable()->after('tenant_id');
            $table->unsignedBigInteger('updatedBy')->nullable()->after('createdBy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('counsels', function (Blueprint $table) {
            $table->dropColumn(['tenant_id', 'createdBy', 'updatedBy']);
        });
    }
};
