<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_notification_dismissals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('notification_type');
            $table->foreignId('car_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('source_record_id');
            $table->date('source_expiry_date')->nullable();
            $table->timestamps();

            $table->unique(
                ['user_id', 'notification_type', 'source_record_id', 'source_expiry_date'],
                'fleet_notification_dismissals_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_notification_dismissals');
    }
};
