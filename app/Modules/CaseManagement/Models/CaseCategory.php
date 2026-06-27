<?php

namespace App\Modules\CaseManagement\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseCategory extends Model
{
    use HasUuids;

    protected $table = 'case_categories';

    protected $fillable = ['name', 'slug', 'required_fields'];

    protected function casts(): array
    {
        return [
            'required_fields' => 'array',
        ];
    }

    public function cases(): HasMany
    {
        return $this->hasMany(CaseModel::class, 'category_id');
    }
}
