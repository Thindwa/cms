<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_import_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('source_file_name');
            $table->string('stored_file_path');
            $table->string('sheet_name')->default('Sheet1');
            $table->string('status', 32)->default('uploaded');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('mapping')->nullable();
            $table->json('options')->nullable();
            $table->json('analysis')->nullable();
            $table->json('dry_run_report')->nullable();
            $table->json('import_report')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_import_batches');
    }
};
