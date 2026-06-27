<?php

namespace App\Modules\CaseManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessBulkCaseImportFileJob;
use App\Modules\CaseManagement\Imports\ExcelCaseImportService;
use App\Modules\CaseManagement\Models\CaseImportBulkBatch;
use App\Modules\CaseManagement\Models\CaseImportBulkFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BulkCaseImportController extends Controller
{
    public function __construct(
        protected ExcelCaseImportService $importService,
    ) {}

    public function create(): View
    {
        $this->authorize('create', CaseImportBulkBatch::class);

        return view('case_management::imports.bulk.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', CaseImportBulkBatch::class);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'sheet_name' => ['nullable', 'string', 'max:100'],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:51200'],
        ]);

        $files = $validated['files'];
        $firstStoredPath = $files[0]->store('imports/cases/bulk', 'local');
        $firstFullPath = Storage::disk('local')->path($firstStoredPath);

        $profile = $this->importService->profile($firstFullPath);
        $sheetName = trim((string) ($validated['sheet_name'] ?? ''));
        if ($sheetName === '' || ! in_array($sheetName, $profile['sheet_names'], true)) {
            $sheetName = $profile['default_sheet'];
        }

        $mapping = $profile['auto_mapping'];
        $options = $this->importService->defaultOptions();
        $analysis = $this->importService->analyze($firstFullPath, $sheetName, $mapping, $options);

        $batch = CaseImportBulkBatch::create([
            'name' => $validated['name'] ?? ('Bulk Import ' . now()->format('Y-m-d H:i')),
            'sheet_name' => $sheetName,
            'status' => 'review_required',
            'created_by' => auth()->id(),
            'mapping' => $mapping,
            'options' => $options,
            'analysis' => array_merge($analysis, ['profile' => $profile]),
            'total_files' => count($files),
        ]);

        foreach ($files as $index => $uploaded) {
            $storedPath = $index === 0 ? $firstStoredPath : $uploaded->store('imports/cases/bulk', 'local');

            CaseImportBulkFile::create([
                'bulk_batch_id' => $batch->id,
                'source_file_name' => $uploaded->getClientOriginalName(),
                'stored_file_path' => $storedPath,
                'status' => 'pending',
            ]);
        }

        return redirect()->route('cases.imports.bulk.show', $batch)
            ->with('success', 'Bulk import uploaded. Review mapping and start processing.');
    }

    public function show(CaseImportBulkBatch $bulk): View
    {
        $this->authorize('view', $bulk);

        $bulk->load(['creator']);
        $files = $bulk->files()
            ->select(['id', 'bulk_batch_id', 'source_file_name', 'status', 'error_message', 'created_at'])
            ->latest('created_at')
            ->paginate((int) config('app.items_per_page', 50));

        $profile = $bulk->analysis['profile'] ?? [
            'sheet_names' => [$bulk->sheet_name],
            'headers' => [],
            'sample_rows' => [],
        ];

        return view('case_management::imports.bulk.show', [
            'batch' => $bulk,
            'files' => $files,
            'profile' => $profile,
            'analysis' => $bulk->analysis,
            'mappingFields' => $this->mappingFields(),
            'options' => $bulk->options ?? $this->importService->defaultOptions(),
        ]);
    }

    public function reanalyze(Request $request, CaseImportBulkBatch $bulk): RedirectResponse
    {
        $this->authorize('execute', $bulk);

        $sheetNames = $bulk->analysis['profile']['sheet_names'] ?? [$bulk->sheet_name];

        $validated = $request->validate([
            'sheet_name' => ['required', 'string', Rule::in($sheetNames)],
            'mapping' => ['required', 'array'],
            'mapping.*' => ['nullable', 'string', 'max:255'],
            'options' => ['required', 'array'],
            'options.duplicate_policy' => ['required', Rule::in(['update_existing', 'create_new', 'add_note_only'])],
            'options.missing_officer_policy' => ['required', Rule::in(['default', 'skip'])],
            'options.default_officer_value' => ['nullable', 'string', 'max:255'],
            'options.date_mode' => ['required', Rule::in(['auto', 'ddmmyyyy', 'excel_serial'])],
            'options.text_policy' => ['required', Rule::in(['clean', 'raw'])],
        ]);

        $firstFile = $bulk->files()
            ->select(['id', 'stored_file_path'])
            ->oldest('created_at')
            ->first();
        if (! $firstFile) {
            return redirect()->route('cases.imports.bulk.show', $bulk)->with('error', 'No files found in this batch.');
        }

        $analysis = $this->importService->analyze(
            Storage::disk('local')->path($firstFile->stored_file_path),
            $validated['sheet_name'],
            $validated['mapping'],
            $validated['options']
        );

        $bulk->update([
            'sheet_name' => $validated['sheet_name'],
            'mapping' => $validated['mapping'],
            'options' => $validated['options'],
            'analysis' => array_merge($analysis, ['profile' => $bulk->analysis['profile'] ?? []]),
            'status' => 'review_required',
        ]);

        return redirect()->route('cases.imports.bulk.show', $bulk)->with('success', 'Bulk analysis updated.');
    }

    public function start(CaseImportBulkBatch $bulk): RedirectResponse
    {
        $this->authorize('execute', $bulk);

        $analysis = $bulk->analysis['stats'] ?? [];
        if (($analysis['blocking_issues'] ?? 0) > 0) {
            return redirect()->route('cases.imports.bulk.show', $bulk)
                ->with('error', 'Resolve blocking issues before starting bulk import.');
        }

        if ($bulk->status === 'processing') {
            return redirect()->route('cases.imports.bulk.show', $bulk)
                ->with('error', 'This bulk import is already processing.');
        }

        if (in_array($bulk->status, ['completed', 'rolled_back'], true)) {
            return redirect()->route('cases.imports.bulk.show', $bulk)
                ->with('error', 'This bulk import batch is already completed and cannot be started again.');
        }

        $pendingCount = $bulk->files()->where('status', 'pending')->count();
        $failedCount = $bulk->files()->where('status', 'failed')->count();
        if (($pendingCount + $failedCount) === 0) {
            return redirect()->route('cases.imports.bulk.show', $bulk)
                ->with('error', 'No pending/failed files available to process for this batch.');
        }

        $bulk->update([
            'status' => 'processing',
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $files = $bulk->files()
            ->select(['id', 'bulk_batch_id', 'source_file_name', 'stored_file_path', 'status'])
            ->whereIn('status', ['pending', 'failed'])
            ->get();
        foreach ($files as $file) {
            dispatch(new ProcessBulkCaseImportFileJob($bulk->id, $file->id));
        }

        return redirect()->route('cases.imports.bulk.show', $bulk)
            ->with('success', 'Bulk import started. Keep this page open to monitor progress.');
    }

    public function progress(CaseImportBulkBatch $bulk): JsonResponse
    {
        $this->authorize('view', $bulk);

        $total = max(1, (int) $bulk->total_files);
        $processed = (int) $bulk->processed_files;
        $percent = (int) floor(($processed / $total) * 100);

        $counts = [
            'pending' => $bulk->files()->where('status', 'pending')->count(),
            'processing' => $bulk->files()->where('status', 'processing')->count(),
            'completed' => $bulk->files()->where('status', 'completed')->count(),
            'failed' => $bulk->files()->where('status', 'failed')->count(),
        ];

        return response()->json([
            'status' => $bulk->status,
            'total_files' => (int) $bulk->total_files,
            'processed_files' => $processed,
            'successful_files' => (int) $bulk->successful_files,
            'failed_files' => (int) $bulk->failed_files,
            'percent' => min(100, $percent),
            'counts' => $counts,
            'done' => in_array($bulk->status, ['completed', 'completed_with_failures'], true),
        ]);
    }

    protected function mappingFields(): array
    {
        return [
            'date_filed' => 'Date Filed',
            'claimant' => 'Claimant / Plaintiff',
            'reference_number' => 'Reference Number',
            'cause_number' => 'Cause Number',
            'description' => 'Description / Latest Issue',
            'officer_dealing' => 'Officer Dealing Source',
            'entered_by_legacy' => 'Entered By (Legacy)',
            'defendant' => 'Defendant / Respondent',
            'hearing_date' => 'Hearing Date',
            'status' => 'Status',
        ];
    }
}
