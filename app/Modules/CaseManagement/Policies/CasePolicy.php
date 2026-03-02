<?php

namespace App\Modules\CaseManagement\Policies;

use App\Models\User;
use App\Modules\CaseManagement\Models\CaseModel;

class CasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cases.view');
    }

    public function view(User $user, CaseModel $case): bool
    {
        return $user->can('cases.view');
    }

    public function create(User $user): bool
    {
        return $user->can('cases.create');
    }

    public function update(User $user, CaseModel $case): bool
    {
        return $user->can('cases.edit');
    }

    public function delete(User $user, CaseModel $case): bool
    {
        return $user->can('cases.edit');
    }
}
