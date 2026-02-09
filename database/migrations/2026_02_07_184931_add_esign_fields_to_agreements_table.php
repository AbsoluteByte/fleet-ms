<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('agreements', function (Blueprint $table) {
            // Add e-signature fields
            $table->string('hellosign_request_id')->nullable()->after('status_id');
            $table->text('hellosign_sign_url')->nullable()->after('hellosign_request_id');
            $table->enum('hellosign_status', ['pending', 'signed', 'declined', 'cancelled'])->nullable()->after('hellosign_sign_url');
            $table->string('hellosign_document_path')->nullable()->after('hellosign_status');
            $table->timestamp('esign_sent_at')->nullable()->after('hellosign_document_path');
            $table->timestamp('esign_completed_at')->nullable()->after('esign_sent_at');
        });
    }

    public function down()
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropColumn([
                'hellosign_request_id',
                'hellosign_sign_url',
                'hellosign_status',
                'hellosign_document_path',
                'esign_sent_at',
                'esign_completed_at'
            ]);
        });
    }
};
