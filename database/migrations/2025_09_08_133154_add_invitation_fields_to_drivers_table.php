<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->boolean('is_invited')->default(false)->after('proof_of_address_document');
            $table->timestamp('invited_at')->nullable()->after('is_invited');
            $table->string('invitation_token')->nullable()->after('invited_at');
            $table->timestamp('invitation_accepted_at')->nullable()->after('invitation_token');
            $table->foreignId('user_id')->nullable()->after('invitation_accepted_at')->constrained()->onDelete('set null');

            // Add index for invitation token
            $table->index('invitation_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            //
        });
    }
};
