<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->string('swap_reason')->nullable()->after('upgraded_from_agreement_id');
            $table->string('swap_phvl_issue_type')->nullable()->after('swap_reason');
            $table->text('swap_phvl_issue_notes')->nullable()->after('swap_phvl_issue_type');
            $table->text('swap_reason_notes')->nullable()->after('swap_phvl_issue_notes');
        });
    }

    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropColumn([
                'swap_reason',
                'swap_phvl_issue_type',
                'swap_phvl_issue_notes',
                'swap_reason_notes',
            ]);
        });
    }
};
