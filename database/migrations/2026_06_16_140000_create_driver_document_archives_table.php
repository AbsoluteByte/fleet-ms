<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_document_archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->string('document_field');
            $table->string('filename');
            $table->string('document_label');
            $table->string('reason');
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at');
            $table->timestamps();

            $table->index(['driver_id', 'document_field']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_document_archives');
    }
};
