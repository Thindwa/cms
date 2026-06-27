<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->index('category_id', 'idx_cases_category_id');
            $table->index('created_at', 'idx_cases_created_at');
        });
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropIndex('idx_cases_category_id');
            $table->dropIndex('idx_cases_created_at');
        });
    }
};
