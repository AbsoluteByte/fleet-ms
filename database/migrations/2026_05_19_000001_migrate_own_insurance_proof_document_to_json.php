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
        Schema::table('agreements', function (Blueprint $table) {
            $table->text('own_insurance_proof_document_backup')->nullable()->after('own_insurance_proof_document');
        });

        DB::statement('UPDATE agreements SET own_insurance_proof_document_backup = own_insurance_proof_document WHERE own_insurance_proof_document IS NOT NULL AND own_insurance_proof_document != \'\'');

        Schema::table('agreements', function (Blueprint $table) {
            $table->dropColumn('own_insurance_proof_document');
        });

        Schema::table('agreements', function (Blueprint $table) {
            $table->json('own_insurance_proof_document')->nullable()->after('own_insurance_policy_number');
        });

        foreach (DB::table('agreements')->select('id', 'own_insurance_proof_document_backup')->cursor() as $row) {
            $payload = empty($row->own_insurance_proof_document_backup) ? null : [$row->own_insurance_proof_document_backup];
            DB::table('agreements')->where('id', $row->id)->update(['own_insurance_proof_document' => $payload]);
        }

        Schema::table('agreements', function (Blueprint $table) {
            $table->dropColumn('own_insurance_proof_document_backup');
        });
    }

    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->text('own_insurance_proof_document_backup')->nullable()->after('own_insurance_proof_document');
        });

        foreach (DB::table('agreements')->select('id', 'own_insurance_proof_document')->cursor() as $row) {
            $first = null;
            if ($row->own_insurance_proof_document) {
                $decoded = json_decode($row->own_insurance_proof_document, true);
                if (is_array($decoded) && $decoded !== []) {
                    $first = $decoded[0];
                }
            }
            DB::table('agreements')->where('id', $row->id)->update(['own_insurance_proof_document_backup' => $first]);
        }

        Schema::table('agreements', function (Blueprint $table) {
            $table->dropColumn('own_insurance_proof_document');
        });

        Schema::table('agreements', function (Blueprint $table) {
            $table->string('own_insurance_proof_document')->nullable()->after('own_insurance_policy_number');
        });

        DB::statement('UPDATE agreements SET own_insurance_proof_document = own_insurance_proof_document_backup WHERE own_insurance_proof_document_backup IS NOT NULL');

        Schema::table('agreements', function (Blueprint $table) {
            $table->dropColumn('own_insurance_proof_document_backup');
        });
    }
};
