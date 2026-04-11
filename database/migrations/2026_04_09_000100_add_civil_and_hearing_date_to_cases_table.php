<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->string('civil_case_number')->nullable()->after('reference_number');
            $table->date('hearing_date')->nullable()->after('date_filed');
            $table->index('civil_case_number');
            $table->index('hearing_date');
        });
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropIndex(['civil_case_number']);
            $table->dropIndex(['hearing_date']);
            $table->dropColumn(['civil_case_number', 'hearing_date']);
        });
    }
};
