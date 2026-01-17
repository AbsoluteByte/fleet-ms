<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Basic, Standard, Premium
            $table->text('description')->nullable();
            $table->enum('billing_period', ['monthly', 'quarterly', 'yearly']);
            $table->decimal('price', 10, 2);
            $table->integer('max_users')->default(5);
            $table->integer('max_vehicles')->default(10);
            $table->integer('max_drivers')->default(10);
            $table->boolean('has_notifications')->default(true);
            $table->boolean('has_reports')->default(false);
            $table->boolean('has_api_access')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('trial_days')->default(30);
            $table->json('features')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('packages');
    }
};
