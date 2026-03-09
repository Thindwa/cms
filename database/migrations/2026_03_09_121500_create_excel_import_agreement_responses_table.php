<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('excel_import_agreement_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_file')->nullable();
            $table->string('source_sheet')->nullable();

            $table->string('row_represents')->nullable();
            $table->string('row_represents_other')->nullable();
            $table->string('officer_dealing_source')->nullable();
            $table->string('officer_dealing_other')->nullable();

            $table->json('field_mapping')->nullable();

            $table->string('duplicate_handling')->nullable();
            $table->string('duplicate_handling_other')->nullable();

            $table->string('missing_required_policy')->nullable();
            $table->string('missing_required_default')->nullable();

            $table->json('date_parsing')->nullable();
            $table->string('date_parsing_other')->nullable();

            $table->string('text_handling')->nullable();
            $table->string('text_handling_other')->nullable();

            $table->string('entered_by_mapping')->nullable();
            $table->string('entered_by_fallback_user')->nullable();

            $table->json('import_scope')->nullable();
            $table->string('import_scope_other')->nullable();

            $table->string('audit_and_rollback')->nullable();
            $table->string('cutover_window')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('excel_import_agreement_responses');
    }
};
