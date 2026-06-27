<?php

namespace App\Modules\CaseManagement\Policies;

use App\Models\User;
use App\Modules\CaseManagement\Models\CaseCategory;

class CaseCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cases.categories.view');
    }

    public function view(User $user, CaseCategory $category): bool
    {
        return $user->can('cases.categories.view');
    }

    public function create(User $user): bool
    {
        return $user->can('cases.categories.create');
    }

    public function update(User $user, CaseCategory $category): bool
    {
        return $user->can('cases.categories.edit');
    }

    public function delete(User $user, CaseCategory $category): bool
    {
        return $user->can('cases.categories.delete');
    }
}
