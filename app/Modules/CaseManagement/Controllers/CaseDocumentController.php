<?php

namespace App\Modules\CaseManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CaseManagement\Models\CaseDocument;
use App\Modules\CaseManagement\Models\CaseModel;
use App\Modules\CaseManagement\Services\CaseDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CaseDocumentController extends Controller
{
    public function __construct(
        protected CaseDocumentService $documentService
    ) {}

    public function store(Request $request, CaseModel $case): RedirectResponse
    {
        $this->authorize('update', $case);
        $request->validate([
            'document' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif'],
        ]);
        $this->documentService->upload($case, $request->file('document'));
        return redirect()->route('cases.show', $case)->with('success', 'Document uploaded.')->with('tab', 'documents');
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
}
