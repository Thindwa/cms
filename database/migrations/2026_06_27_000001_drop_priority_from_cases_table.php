<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('cases', 'priority')) {
            Schema::table('cases', function (Blueprint $table) {
                $table->dropColumn('priority');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('cases', 'priority')) {
            Schema::table('cases', function (Blueprint $table) {
                $table->unsignedTinyInteger('priority')->default(5);
            });
        }
    }
};
