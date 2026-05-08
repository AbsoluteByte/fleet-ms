<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE car_insurances MODIFY start_date DATE NULL');
        DB::statement('ALTER TABLE car_insurances MODIFY expiry_date DATE NULL');
        DB::statement('ALTER TABLE car_insurances MODIFY notify_before_expiry INT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE car_insurances MODIFY start_date DATE NOT NULL');
        DB::statement('ALTER TABLE car_insurances MODIFY expiry_date DATE NOT NULL');
        DB::statement('ALTER TABLE car_insurances MODIFY notify_before_expiry INT NOT NULL');
    }
};

