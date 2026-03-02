<?php

namespace App\Modules\CaseManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CaseManagement\Models\CaseModel;
use App\Modules\CaseManagement\Models\CaseNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CaseNoteController extends Controller
{
    public function store(Request $request, CaseModel $case): RedirectResponse
    {
        $this->authorize('update', $case);
        $request->validate([
            'body' => ['required', 'string', 'max:10000'],
        ]);
        CaseNote::create([
            'case_id' => $case->id,
            'user_id' => auth()->id(),
            'body' => $request->body,
        ]);
        return redirect()->route('cases.show', $case)->with('success', 'Note added.')->with('tab', 'notes');
    }
}
