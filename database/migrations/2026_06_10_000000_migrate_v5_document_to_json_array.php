<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->text('v5_document_backup')->nullable()->after('v5_document');
        });

        DB::statement('UPDATE cars SET v5_document_backup = v5_document WHERE v5_document IS NOT NULL AND v5_document != \'\'');

        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn('v5_document');
        });

        Schema::table('cars', function (Blueprint $table) {
            $table->json('v5_document')->nullable()->after('vin');
        });

        foreach (DB::table('cars')->select('id', 'v5_document_backup')->cursor() as $row) {
            $payload = empty($row->v5_document_backup) ? null : [$row->v5_document_backup];
            DB::table('cars')->where('id', $row->id)->update(['v5_document' => $payload]);
        }

        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn('v5_document_backup');
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->text('v5_document_backup')->nullable()->after('v5_document');
        });

        foreach (DB::table('cars')->select('id', 'v5_document')->cursor() as $row) {
            $first = null;
            if ($row->v5_document) {
                $decoded = json_decode($row->v5_document, true);
                if (is_array($decoded) && $decoded !== []) {
                    $first = $decoded[0];
                }
            }
            DB::table('cars')->where('id', $row->id)->update(['v5_document_backup' => $first]);
        }

        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn('v5_document');
        });

        Schema::table('cars', function (Blueprint $table) {
            $table->string('v5_document')->nullable()->after('vin');
        });

        DB::statement('UPDATE cars SET v5_document = v5_document_backup WHERE v5_document_backup IS NOT NULL');

        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn('v5_document_backup');
        });
    }
};
