<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('invoices', 'status')) {
            DB::statement("ALTER TABLE `invoices` MODIFY `status` VARCHAR(255) NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoices', 'status')) {
            DB::statement("ALTER TABLE `invoices` MODIFY `status` ENUM('draft', 'pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending'");
        }
    }
};
