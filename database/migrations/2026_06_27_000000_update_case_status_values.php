<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('cases')
            ->whereIn('status', ['open', 'in_progress'])
            ->update(['status' => 'active']);

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('cases', function (Blueprint $table) {
                $table->string('status', 32)->default('active')->change();
            });
        }
    }

    public function down(): void
    {
        DB::table('cases')
            ->where('status', 'active')
            ->update(['status' => 'open']);

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('cases', function (Blueprint $table) {
                $table->string('status', 32)->default('open')->change();
            });
        }
    }
};
