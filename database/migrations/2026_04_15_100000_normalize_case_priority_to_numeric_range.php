<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                UPDATE cases
                SET priority = CASE
                    WHEN LOWER(COALESCE(priority, '')) = 'low' THEN '3'
                    WHEN LOWER(COALESCE(priority, '')) = 'medium' THEN '5'
                    WHEN LOWER(COALESCE(priority, '')) = 'high' THEN '8'
                    WHEN priority REGEXP '^[0-9]+$' AND CAST(priority AS UNSIGNED) BETWEEN 1 AND 10 THEN priority
                    ELSE '5'
                END
            ");
        } else {
            DB::statement("
                UPDATE cases
                SET priority = CASE
                    WHEN LOWER(COALESCE(priority, '')) = 'low' THEN '3'
                    WHEN LOWER(COALESCE(priority, '')) = 'medium' THEN '5'
                    WHEN LOWER(COALESCE(priority, '')) = 'high' THEN '8'
                    WHEN COALESCE(priority, '') ~ '^[0-9]+$' AND CAST(priority AS INTEGER) BETWEEN 1 AND 10 THEN priority
                    ELSE '5'
                END
            ");
        }

        Schema::table('cases', function (Blueprint $table): void {
            $table->unsignedTinyInteger('priority')->default(5)->change();
        });
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table): void {
            $table->string('priority', 32)->default('medium')->change();
        });
    }
};

