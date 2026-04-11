<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_hearing_notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('case_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('hearing_date');
            $table->unsignedInteger('days_before');
            $table->string('channel', 32)->default('mail');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->foreign('case_id')->references('id')->on('cases')->cascadeOnDelete();
            $table->unique(['case_id', 'user_id', 'hearing_date', 'channel'], 'case_hearing_notify_unique');
            $table->index(['hearing_date', 'days_before']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_hearing_notifications');
    }
};
