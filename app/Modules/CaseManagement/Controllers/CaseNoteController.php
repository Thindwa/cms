<?php

namespace App\Modules\CaseManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CaseManagement\Models\CaseModel;
use App\Modules\CaseManagement\Models\CaseNote;
use App\Modules\CaseManagement\Services\ActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CaseNoteController extends Controller
{
    public function __construct(
        protected ActivityService $activityService,
    ) {}

    public function store(Request $request, CaseModel $case): RedirectResponse
    {
        $this->authorize('createNote', $case);
        $request->validate([
            'body' => ['required', 'string', 'max:10000'],
        ]);
        CaseNote::create([
            'case_id' => $case->id,
            'user_id' => auth()->id(),
            'body' => $request->body,
        ]);
        $this->activityService->log($case, 'note.created', 'Note added to case');
        return redirect()->route('cases.show', $case)->with('success', 'Note added.')->with('tab', 'notes');
    }

    public function update(Request $request, CaseModel $case, CaseNote $note): RedirectResponse
    {
        $this->authorize('updateNote', $case);
        $this->ensureNoteBelongsToCase($case, $note);

        $request->validate([
            'edit_body' => ['required', 'string', 'max:10000'],
        ]);

        $note->update([
            'body' => $request->string('edit_body')->toString(),
        ]);
        $this->activityService->log($case, 'note.updated', 'Note updated');

        return redirect()->route('cases.show', $case)->with('success', 'Note updated.')->with('tab', 'notes');
    }

    public function destroy(CaseModel $case, CaseNote $note): RedirectResponse
    {
        $this->authorize('deleteNote', $case);
        $this->ensureNoteBelongsToCase($case, $note);

        $note->delete();
        $this->activityService->log($case, 'note.deleted', 'Note deleted from case');

        return redirect()->route('cases.show', $case)->with('success', 'Note deleted.')->with('tab', 'notes');
    }

    protected function ensureNoteBelongsToCase(CaseModel $case, CaseNote $note): void
    {
        abort_unless($note->case_id === $case->id, 404);
    }
}
