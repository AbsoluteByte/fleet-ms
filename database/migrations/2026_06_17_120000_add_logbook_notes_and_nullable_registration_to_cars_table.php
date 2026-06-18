<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->text('logbook_notes')->nullable()->after('log_book_applied_date');
        });

        Schema::table('cars', function (Blueprint $table) {
            $table->string('registration')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn('logbook_notes');
        });

        Schema::table('cars', function (Blueprint $table) {
            $table->string('registration')->nullable(false)->change();
        });
    }
};
