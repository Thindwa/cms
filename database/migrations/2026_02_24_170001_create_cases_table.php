<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('case_number')->unique()->comment('Serial Number');
            $table->date('date_filed')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('defendant')->nullable();
            $table->string('nature_of_claim')->nullable();
            $table->string('claimant')->nullable();
            $table->string('cause_number')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 32)->default('open');
            $table->string('priority', 32)->default('medium');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('cases', function (Blueprint $table) {
            $table->index(['status', 'date_filed']);
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cases');
    }
};
