<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExcelImportAgreementResponse extends Model
{
    protected $fillable = [
        'submitted_by',
        'source_file',
        'source_sheet',
        'row_represents',
        'row_represents_other',
        'officer_dealing_source',
        'officer_dealing_other',
        'field_mapping',
        'duplicate_handling',
        'duplicate_handling_other',
        'missing_required_policy',
        'missing_required_default',
        'date_parsing',
        'date_parsing_other',
        'text_handling',
        'text_handling_other',
        'entered_by_mapping',
        'entered_by_fallback_user',
        'import_scope',
        'import_scope_other',
        'audit_and_rollback',
        'cutover_window',
    ];

    protected function casts(): array
    {
        return [
            'field_mapping' => 'array',
            'date_parsing' => 'array',
            'import_scope' => 'array',
        ];
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
