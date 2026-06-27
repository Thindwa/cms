<?php

namespace App\Modules\CaseManagement\Models;

use App\Helpers\HtmlSanitizer;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CaseModel extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'cases';

    public bool $skipSanitization = false;

    protected static function booted(): void
    {
        static::saving(function (self $case): void {
            if ($case->skipSanitization) {
                return;
            }
            if ($case->isDirty('description')) {
                $case->description = HtmlSanitizer::clean($case->description);
            }
        });
    }

    protected $fillable = [
        'case_number',
        'case_title',
        'date_filed',
        'hearing_date',
        'reference_number',
        'defendant',
        'nature_of_claim',
        'claimant',
        'cause_number',
        'title',
        'description',
        'status',
        'category_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date_filed' => 'date',
            'hearing_date' => 'date',
        ];
    }

    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CaseDocument::class, 'case_id');
    }

    public function trashedDocuments(): HasMany
    {
        return $this->hasMany(CaseDocument::class, 'case_id')->onlyTrashed();
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CaseNote::class, 'case_id')->latest();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CaseCategory::class, 'category_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CaseActivity::class, 'case_id')->latest();
    }

    public function getCategoryNameAttribute(): string
    {
        return $this->category?->name ?? 'Uncategorized';
    }
}
