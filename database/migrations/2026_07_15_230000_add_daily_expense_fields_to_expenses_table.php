<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['car_id']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('car_id')->nullable()->change();
            $table->foreign('car_id')->references('id')->on('cars')->nullOnDelete();

            $table->string('title')->nullable()->after('type');
            $table->string('payment_method')->nullable()->after('amount');
            $table->foreignId('bank_account_id')->nullable()->after('payment_method')->constrained('bank_accounts')->nullOnDelete();
            $table->text('notes')->nullable()->after('document');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['bank_account_id']);
            $table->dropColumn(['title', 'payment_method', 'bank_account_id', 'notes']);
            $table->dropForeign(['car_id']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('car_id')->nullable(false)->change();
            $table->foreign('car_id')->references('id')->on('cars')->onDelete('cascade');
        });
    }
};
