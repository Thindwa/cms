<?php

namespace App\Modules\CaseManagement\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseImportBulkFile extends Model
{
    use HasUuids;

    protected $table = 'case_import_bulk_files';

    protected $fillable = [
        'bulk_batch_id',
        'source_file_name',
        'stored_file_path',
        'status',
        'report',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'report' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(CaseImportBulkBatch::class, 'bulk_batch_id');
    }
}
