<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('agreement_signature_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained()->onDelete('cascade');
            $table->string('token', 100)->unique();
            $table->string('signer_email');
            $table->string('signer_name');
            $table->enum('status', ['pending', 'signed', 'expired'])->default('pending');
            $table->longText('signature_data')->nullable(); // Base64 signature image
            $table->string('ip_address', 50)->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('token');
            $table->index(['agreement_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('agreement_signature_tokens');
    }
};
