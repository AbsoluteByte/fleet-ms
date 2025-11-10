<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('ni_number')->nullable()->after('phone_number');
            $table->string('phd_card_document')->nullable()->after('driver_phd_license_document');
            $table->string('dvla_license_summary')->nullable()->after('phd_card_document');
            $table->string('misc_document')->nullable()->after('dvla_license_summary');
        });
    }

    public function down()
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['ni_number', 'phd_card_document', 'dvla_license_summary', 'misc_document']);
        });
    }
};
