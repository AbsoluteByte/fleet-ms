<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->enum('esign_provider', ['custom', 'hellosign'])->default('custom');
            $table->timestamps();
            $table->unique('tenant_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('settings');
    }
};
