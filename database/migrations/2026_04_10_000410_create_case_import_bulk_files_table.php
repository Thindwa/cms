<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_import_bulk_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('bulk_batch_id')->constrained('case_import_bulk_batches')->cascadeOnDelete();
            $table->string('source_file_name');
            $table->string('stored_file_path');
            $table->string('status', 32)->default('pending');
            $table->json('report')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['bulk_batch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_import_bulk_files');
    }
};
