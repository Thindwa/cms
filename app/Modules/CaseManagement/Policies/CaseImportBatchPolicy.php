<?php

namespace App\Modules\CaseManagement\Policies;

use App\Models\User;
use App\Modules\CaseManagement\Models\CaseImportBatch;

class CaseImportBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cases.import.view');
    }

    public function view(User $user, CaseImportBatch $batch): bool
    {
        return $user->can('cases.import.view');
    }

    public function create(User $user): bool
    {
        return $user->can('cases.import.upload');
    }

    public function execute(User $user, CaseImportBatch $batch): bool
    {
        return $user->can('cases.import.execute');
    }

    public function rollback(User $user, CaseImportBatch $batch): bool
    {
        return $user->can('cases.import.rollback');
    }

    public function reset(User $user): bool
    {
        return $user->can('cases.import.reset');
    }
}
