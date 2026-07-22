<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->text('payment_follow_up_notes')->nullable()->after('is_active');
            $table->dateTime('payment_remind_at')->nullable()->after('payment_follow_up_notes');
            $table->dateTime('payment_reminder_dismissed_at')->nullable()->after('payment_remind_at');
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn([
                'payment_follow_up_notes',
                'payment_remind_at',
                'payment_reminder_dismissed_at',
            ]);
        });
    }
};
