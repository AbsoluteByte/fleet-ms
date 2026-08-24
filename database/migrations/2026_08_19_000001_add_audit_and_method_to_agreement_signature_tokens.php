<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agreement_signature_tokens', function (Blueprint $table) {
            $table->timestamp('opened_at')->nullable()->after('expires_at');
            $table->string('opened_ip', 45)->nullable()->after('opened_at');
            $table->text('referrer')->nullable()->after('opened_ip');
            $table->text('user_agent')->nullable()->after('referrer');
            $table->string('accept_language', 255)->nullable()->after('user_agent');
            $table->text('landing_url')->nullable()->after('accept_language');
            $table->string('signature_method', 20)->nullable()->after('signature_data');
            $table->string('typed_name', 255)->nullable()->after('signature_method');
        });
    }

    public function down(): void
    {
        Schema::table('agreement_signature_tokens', function (Blueprint $table) {
            $table->dropColumn([
                'opened_at',
                'opened_ip',
                'referrer',
                'user_agent',
                'accept_language',
                'landing_url',
                'signature_method',
                'typed_name',
            ]);
        });
    }
};
