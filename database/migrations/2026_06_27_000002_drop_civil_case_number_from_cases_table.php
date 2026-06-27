<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('cases', 'civil_case_number')) {
            Schema::table('cases', function (Blueprint $table) {
                $table->dropColumn('civil_case_number');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('cases', 'civil_case_number')) {
            Schema::table('cases', function (Blueprint $table) {
                $table->string('civil_case_number')->nullable()->after('reference_number');
            });
        }
    }
};
