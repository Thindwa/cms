<?php

namespace App\Modules\CaseManagement\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CaseCategory extends Model
{
    use HasUuids;

    protected $table = 'case_categories';

    protected $fillable = ['name', 'slug'];
}
