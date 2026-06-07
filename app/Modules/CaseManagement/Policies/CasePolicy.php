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
        return $user->can('cases.delete');
    }

    public function createNote(User $user, CaseModel $case): bool
    {
        return $user->can('cases.notes.add');
    }

    public function updateNote(User $user, CaseModel $case): bool
    {
        return $user->can('cases.notes.edit');
    }

    public function deleteNote(User $user, CaseModel $case): bool
    {
        return $user->can('cases.notes.delete');
    }

    public function uploadDocument(User $user, CaseModel $case): bool
    {
        return $user->can('cases.documents.upload');
    }

    public function deleteDocument(User $user, CaseModel $case): bool
    {
        return $user->can('cases.documents.delete');
    }

    public function restoreDocument(User $user, CaseModel $case): bool
    {
        return $user->can('cases.documents.restore');
    }
}
