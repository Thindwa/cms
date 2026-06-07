<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->string('module', 64)->nullable()->after('action');
            $table->string('level', 16)->default('info')->after('module');
            $table->string('outcome', 16)->default('success')->after('level');
            $table->string('request_id', 64)->nullable()->after('auditable_id');
            $table->string('session_id', 128)->nullable()->after('request_id');
            $table->string('route_name', 191)->nullable()->after('session_id');
            $table->string('method', 16)->nullable()->after('route_name');
            $table->text('url')->nullable()->after('method');
            $table->string('actor_name', 255)->nullable()->after('user_agent');
            $table->string('actor_email', 255)->nullable()->after('actor_name');
            $table->json('tags')->nullable()->after('new_values');
            $table->json('context')->nullable()->after('tags');
        });

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->index(['module', 'action', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['request_id']);
            $table->index(['outcome', 'level', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropIndex(['module', 'action', 'created_at']);
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['request_id']);
            $table->dropIndex(['outcome', 'level', 'created_at']);
        });

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropColumn([
                'module',
                'level',
                'outcome',
                'request_id',
                'session_id',
                'route_name',
                'method',
                'url',
                'actor_name',
                'actor_email',
                'tags',
                'context',
            ]);
        });
    }
};
