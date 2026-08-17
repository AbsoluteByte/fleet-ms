<?php

use App\Models\Status;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agreements') && ! Schema::hasColumn('agreements', 'renewed_from_agreement_id')) {
            Schema::table('agreements', function (Blueprint $table) {
                $table->unsignedBigInteger('renewed_from_agreement_id')->nullable()->after('upgraded_from_agreement_id');
            });
        }

        if (! Schema::hasTable('agreements') || ! Schema::hasTable('statuses')) {
            return;
        }

        $expiredId = Status::query()
            ->where('type', 'agreement')
            ->where('name', 'Expired')
            ->value('id');
        $terminatedId = Status::query()
            ->where('type', 'agreement')
            ->where('name', 'Terminated')
            ->value('id');

        if (! $expiredId || ! $terminatedId) {
            return;
        }

        DB::table('agreements')
            ->where('status_id', $expiredId)
            ->whereNotNull('closing_date')
            ->update(['status_id' => $terminatedId]);
    }

    public function down(): void
    {
        if (Schema::hasTable('agreements') && Schema::hasColumn('agreements', 'renewed_from_agreement_id')) {
            Schema::table('agreements', function (Blueprint $table) {
                $table->dropColumn('renewed_from_agreement_id');
            });
        }
    }
};
