<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->boolean('tracker_installed')->default(false)->after('logbook_notes');
            $table->string('tracker_status', 20)->nullable()->after('tracker_installed');
            $table->text('tracker_notes')->nullable()->after('tracker_status');
            $table->boolean('dashcam_installed')->default(false)->after('tracker_notes');
            $table->string('dashcam_status', 20)->nullable()->after('dashcam_installed');
            $table->text('dashcam_notes')->nullable()->after('dashcam_status');
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn([
                'tracker_installed',
                'tracker_status',
                'tracker_notes',
                'dashcam_installed',
                'dashcam_status',
                'dashcam_notes',
            ]);
        });
    }
};
