<?php

namespace App\Modules\CaseManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseImportBatch extends Model
{
    use HasUuids;

    protected $table = 'case_import_batches';

    protected $fillable = [
        'source_file_name',
        'stored_file_path',
        'sheet_name',
        'status',
        'created_by',
        'mapping',
        'options',
        'analysis',
        'dry_run_report',
        'import_report',
        'executed_at',
    ];

    protected function casts(): array
    {
        return [
            'mapping' => 'array',
            'options' => 'array',
            'analysis' => 'array',
            'dry_run_report' => 'array',
            'import_report' => 'array',
            'executed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
