<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_import_batches', function (Blueprint $table) {
            $table->index('created_at', 'idx_import_batches_created_at');
        });

        Schema::table('case_import_bulk_batches', function (Blueprint $table) {
            $table->index('created_at', 'idx_import_bulk_batches_created_at');
        });

        Schema::table('case_import_bulk_files', function (Blueprint $table) {
            $table->index('created_at', 'idx_import_bulk_files_created_at');
        });
    }

    public function down(): void
    {
        Schema::table('case_import_batches', function (Blueprint $table) {
            $table->dropIndex('idx_import_batches_created_at');
        });

        Schema::table('case_import_bulk_batches', function (Blueprint $table) {
            $table->dropIndex('idx_import_bulk_batches_created_at');
        });

        Schema::table('case_import_bulk_files', function (Blueprint $table) {
            $table->dropIndex('idx_import_bulk_files_created_at');
        });
    }
};
