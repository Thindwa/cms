<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_import_bulk_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->string('sheet_name')->default('Sheet1');
            $table->string('status', 32)->default('review_required');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('mapping')->nullable();
            $table->json('options')->nullable();
            $table->json('analysis')->nullable();
            $table->unsignedInteger('total_files')->default(0);
            $table->unsignedInteger('processed_files')->default(0);
            $table->unsignedInteger('successful_files')->default(0);
            $table->unsignedInteger('failed_files')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_import_bulk_batches');
    }
};
