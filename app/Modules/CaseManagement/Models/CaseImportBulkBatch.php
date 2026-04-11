<?php

namespace App\Modules\CaseManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseImportBulkBatch extends Model
{
    use HasUuids;

    protected $table = 'case_import_bulk_batches';

    protected $fillable = [
        'name',
        'sheet_name',
        'status',
        'created_by',
        'mapping',
        'options',
        'analysis',
        'total_files',
        'processed_files',
        'successful_files',
        'failed_files',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'mapping' => 'array',
            'options' => 'array',
            'analysis' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function files(): HasMany
    {
        return $this->hasMany(CaseImportBulkFile::class, 'bulk_batch_id');
    }
}
