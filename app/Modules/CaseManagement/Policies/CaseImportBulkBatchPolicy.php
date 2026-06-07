<?php

namespace App\Modules\CaseManagement\Policies;

use App\Models\User;
use App\Modules\CaseManagement\Models\CaseImportBulkBatch;

class CaseImportBulkBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cases.import.view');
    }

    public function view(User $user, CaseImportBulkBatch $batch): bool
    {
        return $user->can('cases.import.view');
    }

    public function create(User $user): bool
    {
        return $user->can('cases.import.bulk');
    }

    public function execute(User $user, CaseImportBulkBatch $batch): bool
    {
        return $user->can('cases.import.execute');
    }
}
