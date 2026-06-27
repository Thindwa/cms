<?php

namespace App\Modules\CaseManagement\Services;

use App\Modules\CaseManagement\Models\CaseActivity;
use App\Modules\CaseManagement\Models\CaseModel;
use Illuminate\Database\Eloquent\Collection;

class ActivityService
{
    public function log(
        CaseModel $case,
        string $action,
        ?string $description = null,
        ?array $properties = null,
    ): CaseActivity {
        return CaseActivity::create([
            'case_id' => $case->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'properties' => $properties,
        ]);
    }

    public function forCase(CaseModel $case, int $limit = 50): Collection
    {
        return $case->activities()
            ->with('user')
            ->latest()
            ->limit($limit)
            ->get();
    }
}
