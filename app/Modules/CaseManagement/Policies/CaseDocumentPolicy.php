<?php

namespace App\Modules\CaseManagement\Policies;

use App\Models\User;
use App\Modules\CaseManagement\Models\CaseDocument;

class CaseDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cases.documents.recycle_bin');
    }

    public function purge(User $user, CaseDocument $document): bool
    {
        return $user->can('cases.documents.purge');
    }
}
