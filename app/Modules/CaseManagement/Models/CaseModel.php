<?php

namespace App\Modules\CaseManagement\Models;

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

    protected $fillable = [
        'case_number',
        'date_filed',
        'reference_number',
        'defendant',
        'nature_of_claim',
        'claimant',
        'cause_number',
        'title',
        'description',
        'assigned_to',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date_filed' => 'date',
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

    public function notes(): HasMany
    {
        return $this->hasMany(CaseNote::class, 'case_id');
    }
}
