<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->boolean('tag_installed')->default(false)->after('dashcam_notes');
            $table->string('tag_status', 20)->nullable()->after('tag_installed');
            $table->text('tag_notes')->nullable()->after('tag_status');
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn([
                'tag_installed',
                'tag_status',
                'tag_notes',
            ]);
        });
    }
};
