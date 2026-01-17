<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tenant_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['admin', 'user'])->default('user'); // Role in THIS tenant
            $table->boolean('is_primary')->default(false); // Default company
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id']); // Prevent duplicate entries
            $table->index(['user_id', 'is_primary']); // Fast primary tenant lookup
        });
    }

    public function down()
    {
        Schema::dropIfExists('tenant_user');
    }
};
