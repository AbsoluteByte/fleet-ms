<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_reservation_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_reservation_id')->constrained('car_reservations')->cascadeOnDelete();
            $table->string('payment_method')->nullable();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('posting_status')->default('pending');
            $table->timestamps();

            $table->index(['car_reservation_id', 'posting_status']);
        });

        if (! Schema::hasTable('car_reservations')) {
            return;
        }

        $reservations = DB::table('car_reservations')
            ->where('amount_paid', '>', 0)
            ->get(['id', 'payment_method', 'bank_account_id', 'amount_paid', 'posting_status']);

        foreach ($reservations as $reservation) {
            DB::table('car_reservation_payments')->insert([
                'car_reservation_id' => $reservation->id,
                'payment_method' => $reservation->payment_method ?: 'Cash',
                'bank_account_id' => $reservation->bank_account_id,
                'amount' => $reservation->amount_paid,
                'posting_status' => $reservation->posting_status ?: 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('car_reservation_payments');
    }
};
