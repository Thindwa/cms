<?php

namespace App\Modules\CaseManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CaseManagement\Models\CaseDocument;
use App\Modules\CaseManagement\Models\CaseModel;
use App\Modules\CaseManagement\Services\CaseDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class CaseDocumentController extends Controller
{
    public function __construct(
        protected CaseDocumentService $documentService
    ) {}

    public function store(Request $request, CaseModel $case): RedirectResponse
    {
        $this->authorize('uploadDocument', $case);
        $request->validate([
            'documents' => ['required', 'array', 'min:1'],
            'documents.*' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif'],
            'document_title' => ['nullable', 'string', 'max:255'],
            'document_details' => ['nullable', 'string'],
        ]);

        $title = $request->string('document_title')->toString() ?: null;
        $details = $request->string('document_details')->toString() ?: null;
        $files = $request->file('documents', []);
        $uploaded = 0;
        foreach ($files as $file) {
            $this->documentService->upload($case, $file, $title, $details);
            $uploaded++;
        }

        $message = $uploaded === 1 ? '1 document uploaded.' : "{$uploaded} documents uploaded.";

        return redirect()->route('cases.show', $case)->with('success', $message)->with('tab', 'documents');
    }

    public function download(CaseModel $case, CaseDocument $document): StreamedResponse|RedirectResponse
    {
        $this->authorize('view', $case);
        if ($document->case_id !== $case->id) {
            abort(404);
        }
        if (! $this->documentService->exists($document)) {
            return redirect()->route('cases.show', $case)->with('error', 'File not found.');
        }
        $path = \Illuminate\Support\Facades\Storage::disk('local')->path($document->file_path);
        return response()->streamDownload(
            fn () => print(file_get_contents($path)),
            $document->original_name,
            ['Content-Type' => $document->mime_type ?? 'application/octet-stream']
        );
    }

    public function destroy(CaseModel $case, string $document): RedirectResponse
    {
        $this->authorize('deleteDocument', $case);
        $doc = CaseDocument::query()->where('case_id', $case->id)->findOrFail($document);
        $this->documentService->softDelete($doc);

        return redirect()->route('cases.show', $case)->with('success', 'Document moved to recycle bin.')->with('tab', 'documents');
    }

    public function restore(CaseModel $case, string $document): RedirectResponse
    {
        $this->authorize('restoreDocument', $case);
        $doc = CaseDocument::onlyTrashed()->where('case_id', $case->id)->findOrFail($document);
        $this->documentService->restore($doc);

        return redirect()->route('cases.show', $case)->with('success', 'Document restored successfully.')->with('tab', 'documents');
    }

    public function recycleBin(Request $request): View
    {
        $this->authorize('viewAny', CaseDocument::class);

        $query = CaseDocument::onlyTrashed()
            ->with(['case:id,case_number,title', 'deletedByUser:id,name'])
            ->latest('deleted_at');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('original_name', 'like', '%' . $search . '%')
                    ->orWhereHas('case', function ($cq) use ($search) {
                        $cq->where('case_number', 'like', '%' . $search . '%')
                            ->orWhere('title', 'like', '%' . $search . '%');
                    });
            });
        }

        $documents = $query->paginate(20)->withQueryString();

        return view('case_management::documents.recycle-bin', compact('documents'));
    }

    public function purge(string $document): RedirectResponse
    {
        $doc = CaseDocument::onlyTrashed()->findOrFail($document);
        $this->authorize('purge', $doc);
        $this->documentService->purge($doc);

        return redirect()->route('cases.documents.recycle-bin')
            ->with('success', 'Document deleted permanently.');
    }
}
