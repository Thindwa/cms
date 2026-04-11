<?php

namespace App\Modules\CaseManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CaseManagement\Imports\ExcelCaseImportService;
use App\Modules\CaseManagement\Models\CaseImportBatch;
use App\Modules\CaseManagement\Models\CaseImportBulkBatch;
use App\Modules\CaseManagement\Models\CaseImportBulkFile;
use App\Modules\CaseManagement\Models\CaseModel;
use App\Modules\CaseManagement\Models\CaseNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CaseImportController extends Controller
{
    public function __construct(
        protected ExcelCaseImportService $importService,
    ) {}

    public function index(): View
    {
        $this->authorizeImport();

        $batches = CaseImportBatch::query()
            ->with('creator')
            ->latest('created_at')
            ->paginate(20);

        $bulkBatches = CaseImportBulkBatch::query()
            ->with('creator')
            ->latest('created_at')
            ->limit(20)
            ->get();

        return view('case_management::imports.index', compact('batches', 'bulkBatches'));
    }

    public function create(): View
    {
        $this->authorizeImport();

        return view('case_management::imports.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeImport();

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:51200'],
            'sheet_name' => ['nullable', 'string', 'max:100'],
        ]);

        $uploaded = $validated['file'];
        $storedPath = $uploaded->store('imports/cases', 'local');
        $fullPath = Storage::disk('local')->path($storedPath);

        $profile = $this->importService->profile($fullPath);
        $sheetName = trim((string) ($validated['sheet_name'] ?? ''));
        if ($sheetName === '' || ! in_array($sheetName, $profile['sheet_names'], true)) {
            $sheetName = $profile['default_sheet'];
        }

        $mapping = $profile['auto_mapping'];
        $options = $profile['default_options'];
        $analysis = $this->importService->analyze($fullPath, $sheetName, $mapping, $options);

        $batch = CaseImportBatch::create([
            'source_file_name' => $uploaded->getClientOriginalName(),
            'stored_file_path' => $storedPath,
            'sheet_name' => $sheetName,
            'status' => 'review_required',
            'created_by' => auth()->id(),
            'mapping' => $mapping,
            'options' => $options,
            'analysis' => array_merge($analysis, ['profile' => $profile]),
        ]);

        return redirect()
            ->route('cases.imports.show', $batch)
            ->with('success', 'File uploaded. Review mappings and resolve issues before import.');
    }

    public function show(CaseImportBatch $import): View
    {
        $this->authorizeImport();

        $profile = $import->analysis['profile'] ?? [
            'sheet_names' => [$import->sheet_name],
            'headers' => [],
            'sample_rows' => [],
        ];

        $mappingFields = $this->mappingFields();
        $options = $import->options ?? $this->importService->defaultOptions();

        return view('case_management::imports.show', [
            'batch' => $import,
            'profile' => $profile,
            'analysis' => $import->analysis,
            'mappingFields' => $mappingFields,
            'options' => $options,
        ]);
    }

    public function reanalyze(Request $request, CaseImportBatch $import): RedirectResponse
    {
        $this->authorizeImport();

        $sheetNames = $import->analysis['profile']['sheet_names'] ?? [$import->sheet_name];

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

        $fullPath = Storage::disk('local')->path($import->stored_file_path);
        $analysis = $this->importService->analyze(
            $fullPath,
            $validated['sheet_name'],
            $validated['mapping'],
            $validated['options']
        );

        $import->update([
            'sheet_name' => $validated['sheet_name'],
            'mapping' => $validated['mapping'],
            'options' => $validated['options'],
            'analysis' => array_merge($analysis, [
                'profile' => $import->analysis['profile'] ?? ['sheet_names' => [$validated['sheet_name']]],
            ]),
            'status' => 'review_required',
        ]);

        return redirect()->route('cases.imports.show', $import)
            ->with('success', 'Analysis updated.');
    }

    public function dryRun(CaseImportBatch $import): RedirectResponse
    {
        $this->authorizeImport();

        $fullPath = Storage::disk('local')->path($import->stored_file_path);
        $report = $this->importService->importConfigured(
            $fullPath,
            $import->sheet_name,
            $import->mapping ?? [],
            $import->options ?? [],
            auth()->id(),
            true
        );

        $import->update([
            'dry_run_report' => $report,
            'status' => 'dry_run_completed',
        ]);

        return redirect()->route('cases.imports.show', $import)
            ->with('success', 'Dry-run completed. Review report before executing final import.');
    }

    public function execute(Request $request, CaseImportBatch $import): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorizeImport();

        if ($import->status === 'imported' && ! empty($import->import_report)) {
            $message = 'This batch has already been imported. Use rollback first before importing again.';
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $message,
                ], 422);
            }

            return redirect()->route('cases.imports.show', $import)->with('error', $message);
        }

        $analysis = $import->analysis['stats'] ?? [];
        if (($analysis['blocking_issues'] ?? 0) > 0) {
            return redirect()->route('cases.imports.show', $import)
                ->with('error', 'Resolve blocking issues before running final import.');
        }

        $fullPath = Storage::disk('local')->path($import->stored_file_path);
        $report = $this->importService->importConfigured(
            $fullPath,
            $import->sheet_name,
            $import->mapping ?? [],
            $import->options ?? [],
            auth()->id(),
            false
        );

        $import->update([
            'import_report' => $report,
            'executed_at' => now(),
            'status' => 'imported',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Import executed successfully.',
                'redirect_url' => route('cases.index'),
            ]);
        }

        return redirect()->route('cases.index')
            ->with('success', 'Import executed successfully. Redirected to Case List.');
    }

    public function rollback(CaseImportBatch $import): RedirectResponse
    {
        $this->authorizeImport();

        $report = $import->import_report ?? [];
        $rollback = $report['rollback'] ?? null;
        if (! is_array($rollback)) {
            return redirect()->route('cases.imports.show', $import)
                ->with('error', 'No rollback data found for this import batch.');
        }

        $createdCaseIds = array_values(array_filter($rollback['created_case_ids'] ?? []));
        $updatedCases = array_values($rollback['updated_cases'] ?? []);
        $createdNoteIds = array_values(array_filter($rollback['created_note_ids'] ?? []));

        if ($createdCaseIds === [] && $updatedCases === [] && $createdNoteIds === []) {
            return redirect()->route('cases.imports.show', $import)
                ->with('error', 'Nothing to rollback for this batch.');
        }

        DB::transaction(function () use ($createdCaseIds, $updatedCases, $createdNoteIds): void {
            $this->applyRollbackPayload([
                'created_case_ids' => $createdCaseIds,
                'updated_cases' => $updatedCases,
                'created_note_ids' => $createdNoteIds,
            ]);
        });

        $report['rollback_meta'] = [
            'rolled_back_at' => now()->toDateTimeString(),
            'rolled_back_by' => auth()->id(),
            'created_cases_removed' => count($createdCaseIds),
            'updated_cases_restored' => count($updatedCases),
            'notes_removed' => count($createdNoteIds),
        ];

        $import->update([
            'import_report' => $report,
            'status' => 'rolled_back',
        ]);

        return redirect()->route('cases.imports.show', $import)
            ->with('success', 'Import rollback completed.');
    }

    public function resetSelected(Request $request): RedirectResponse
    {
        $this->authorizeImport();

        $validated = $request->validate([
            'single_batch_ids' => ['nullable', 'array'],
            'single_batch_ids.*' => ['string', Rule::exists('case_import_batches', 'id')],
            'bulk_batch_ids' => ['nullable', 'array'],
            'bulk_batch_ids.*' => ['string', Rule::exists('case_import_bulk_batches', 'id')],
        ]);

        $singleIds = array_values(array_unique($validated['single_batch_ids'] ?? []));
        $bulkIds = array_values(array_unique($validated['bulk_batch_ids'] ?? []));
        if ($singleIds === [] && $bulkIds === []) {
            return redirect()->route('cases.imports.index')
                ->with('error', 'Select at least one import batch to reset.');
        }

        $singleBatchesForGuard = CaseImportBatch::query()->whereIn('id', $singleIds)->get();
        $notRolledBack = $singleBatchesForGuard->filter(function (CaseImportBatch $batch): bool {
            return $batch->status === 'imported' && empty($batch->import_report['rollback_meta']);
        });
        if ($notRolledBack->isNotEmpty()) {
            $names = $notRolledBack->pluck('source_file_name')->implode(', ');
            return redirect()->route('cases.imports.index')->with(
                'error',
                "Selected import batch(es) are still imported and must be rolled back first: {$names}"
            );
        }

        $bulkBatchesForGuard = CaseImportBulkBatch::query()
            ->with('files')
            ->whereIn('id', $bulkIds)
            ->get();

        $bulkProcessing = $bulkBatchesForGuard->filter(function (CaseImportBulkBatch $batch): bool {
            return $batch->status === 'processing';
        });
        if ($bulkProcessing->isNotEmpty()) {
            $names = $bulkProcessing->pluck('name')->implode(', ');
            return redirect()->route('cases.imports.index')->with(
                'error',
                "Selected bulk import batch(es) are still processing and cannot be reset: {$names}"
            );
        }

        $bulkMissingRollback = $bulkBatchesForGuard->filter(function (CaseImportBulkBatch $batch): bool {
            foreach ($batch->files as $file) {
                if ($file->status !== 'completed') {
                    continue;
                }

                $rollback = $file->report['rollback'] ?? null;
                if (! is_array($rollback)) {
                    return true;
                }
            }

            return false;
        });
        if ($bulkMissingRollback->isNotEmpty()) {
            $names = $bulkMissingRollback->pluck('name')->implode(', ');
            return redirect()->route('cases.imports.index')->with(
                'error',
                "Selected bulk import batch(es) have missing rollback data and cannot be reset safely: {$names}"
            );
        }

        $summary = [
            'single_batches' => 0,
            'bulk_batches' => 0,
            'rolled_back_cases_removed' => 0,
            'rolled_back_cases_restored' => 0,
            'rolled_back_notes_removed' => 0,
        ];
        $pathsToDelete = [];

        DB::transaction(function () use (&$summary, &$pathsToDelete, $singleIds, $bulkIds): void {
            $singleBatches = CaseImportBatch::query()
                ->whereIn('id', $singleIds)
                ->orderByDesc('executed_at')
                ->orderByDesc('created_at')
                ->get();

            foreach ($singleBatches as $batch) {
                $summary['single_batches']++;
                $pathsToDelete[] = $batch->stored_file_path;
                $rollback = $batch->import_report['rollback'] ?? null;
                if (is_array($rollback)) {
                    $result = $this->applyRollbackPayload($rollback);
                    $summary['rolled_back_cases_removed'] += $result['cases_removed'];
                    $summary['rolled_back_cases_restored'] += $result['cases_restored'];
                    $summary['rolled_back_notes_removed'] += $result['notes_removed'];
                }
            }

            $bulkBatches = CaseImportBulkBatch::query()->whereIn('id', $bulkIds)->get();
            foreach ($bulkBatches as $bulk) {
                $summary['bulk_batches']++;
            }

            $bulkFiles = CaseImportBulkFile::query()
                ->whereIn('bulk_batch_id', $bulkIds)
                ->orderByDesc('completed_at')
                ->orderByDesc('created_at')
                ->get();

            foreach ($bulkFiles as $bulkFile) {
                $pathsToDelete[] = $bulkFile->stored_file_path;
                $rollback = $bulkFile->report['rollback'] ?? null;
                if (is_array($rollback)) {
                    $result = $this->applyRollbackPayload($rollback);
                    $summary['rolled_back_cases_removed'] += $result['cases_removed'];
                    $summary['rolled_back_cases_restored'] += $result['cases_restored'];
                    $summary['rolled_back_notes_removed'] += $result['notes_removed'];
                }
            }

            CaseImportBulkFile::query()->whereIn('bulk_batch_id', $bulkIds)->delete();
            CaseImportBulkBatch::query()->whereIn('id', $bulkIds)->delete();
            CaseImportBatch::query()->whereIn('id', $singleIds)->delete();
        });

        $pathsToDelete = array_values(array_unique(array_filter($pathsToDelete)));
        if ($pathsToDelete !== []) {
            Storage::disk('local')->delete($pathsToDelete);
        }

        return redirect()->route('cases.imports.index')->with(
            'success',
            sprintf(
                'Selected import reset completed. Cleared %d single batches, %d bulk batches. Rolled back: %d cases removed, %d cases restored, %d notes removed.',
                $summary['single_batches'],
                $summary['bulk_batches'],
                $summary['rolled_back_cases_removed'],
                $summary['rolled_back_cases_restored'],
                $summary['rolled_back_notes_removed'],
            )
        );
    }

    protected function authorizeImport(): void
    {
        abort_unless(auth()->user()?->can('cases.import'), 403);
    }

    protected function mappingFields(): array
    {
        return [
            'date_filed' => 'Date Filed',
            'claimant' => 'Claimant / Plaintiff',
            'reference_number' => 'Reference Number',
            'cause_number' => 'Cause Number',
            'civil_case_number' => 'Civil Case Number',
            'description' => 'Description / Latest Issue',
            'officer_dealing' => 'Officer Dealing Source',
            'entered_by_legacy' => 'Entered By (Legacy)',
            'defendant' => 'Defendant / Respondent',
            'hearing_date' => 'Hearing Date',
        ];
    }

    /**
     * @return array{cases_removed:int,cases_restored:int,notes_removed:int}
     */
    protected function applyRollbackPayload(array $rollback): array
    {
        $createdCaseIds = array_values(array_filter($rollback['created_case_ids'] ?? []));
        $updatedCases = array_values($rollback['updated_cases'] ?? []);
        $createdNoteIds = array_values(array_filter($rollback['created_note_ids'] ?? []));

        $notesRemoved = 0;
        if ($createdNoteIds !== []) {
            $notesRemoved = CaseNote::query()->whereIn('id', $createdNoteIds)->delete();
        }

        $casesRestored = 0;
        foreach ($updatedCases as $entry) {
            $caseId = $entry['case_id'] ?? null;
            $oldValues = $entry['old_values'] ?? null;
            if (! $caseId || ! is_array($oldValues) || $oldValues === []) {
                continue;
            }

            $case = CaseModel::withTrashed()->find($caseId);
            if (! $case) {
                continue;
            }

            $case->update($oldValues);
            $casesRestored++;
        }

        $casesRemoved = 0;
        if ($createdCaseIds !== []) {
            $cases = CaseModel::withTrashed()->whereIn('id', $createdCaseIds)->get();
            foreach ($cases as $case) {
                $case->forceDelete();
                $casesRemoved++;
            }
        }

        return [
            'cases_removed' => $casesRemoved,
            'cases_restored' => $casesRestored,
            'notes_removed' => $notesRemoved,
        ];
    }
}
