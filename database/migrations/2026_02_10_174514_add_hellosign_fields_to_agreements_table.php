<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->string('hellosign_request_id')->nullable()->after('status_id');
            $table->string('hellosign_sign_url')->nullable()->after('hellosign_request_id');
            $table->string('hellosign_status')->nullable()->after('hellosign_sign_url');
            $table->timestamp('esign_sent_at')->nullable()->after('hellosign_status');
            $table->timestamp('esign_completed_at')->nullable()->after('esign_sent_at');
            $table->string('esign_document_path')->nullable()->after('esign_completed_at');

            // Index
            $table->index('hellosign_request_id');
        });
    }

    public function down()
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropIndex(['hellosign_request_id']);
            $table->dropColumn([
                'hellosign_request_id',
                'hellosign_sign_url',
                'hellosign_status',
                'esign_sent_at',
                'esign_completed_at',
                'esign_document_path'
            ]);
        });
    }
};
