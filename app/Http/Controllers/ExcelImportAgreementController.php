<?php

namespace App\Http\Controllers;

use App\Models\ExcelImportAgreementResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExcelImportAgreementController extends Controller
{
    public function create(): View
    {
        return view('excel_import_agreement.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'source_file' => ['nullable', 'string', 'max:255'],
            'source_sheet' => ['nullable', 'string', 'max:255'],

            'row_represents' => ['required', 'string', 'max:64'],
            'row_represents_other' => ['nullable', 'string', 'max:255'],

            'officer_dealing_source' => ['required', 'string', 'max:64'],
            'officer_dealing_other' => ['nullable', 'string', 'max:255'],

            'mapping_target' => ['required', 'array'],
            'mapping_target.*' => ['nullable', 'string', 'max:255'],
            'mapping_confirmed' => ['required', 'array'],
            'mapping_confirmed.*' => ['required', 'string', 'in:yes,no,needs_review'],
            'mapping_note' => ['nullable', 'array'],
            'mapping_note.*' => ['nullable', 'string', 'max:255'],

            'duplicate_handling' => ['required', 'string', 'max:64'],
            'duplicate_handling_other' => ['nullable', 'string', 'max:255'],

            'missing_required_policy' => ['required', 'string', 'max:64'],
            'missing_required_default' => ['nullable', 'string', 'max:255'],

            'date_parsing' => ['required', 'array', 'min:1'],
            'date_parsing.*' => ['string', 'max:64'],
            'date_parsing_other' => ['nullable', 'string', 'max:255'],

            'text_handling' => ['required', 'string', 'max:64'],
            'text_handling_other' => ['nullable', 'string', 'max:255'],

            'entered_by_mapping' => ['required', 'string', 'max:64'],
            'entered_by_fallback_user' => ['nullable', 'string', 'max:255'],

            'import_scope' => ['required', 'array', 'min:1'],
            'import_scope.*' => ['string', 'max:64'],
            'import_scope_other' => ['nullable', 'string', 'max:255'],

            'audit_and_rollback' => ['required', 'string', 'max:64'],
            'cutover_window' => ['required', 'string', 'max:64'],
        ]);

        $mappingKeys = [
            'date' => 'DATE',
            'plaintiff' => 'PLAINTIFF',
            'reference_no' => 'REFERNCE NO',
            'cause_no' => 'CAUSE NO',
            'latest_issue' => 'LATEST ISSUE',
            'file_moved_to' => 'FILE MOVED TO',
            'entered_by' => 'ENTERED BY',
            'legal_opinion_respondant' => 'LEGAL OPINION RESPONDANT',
        ];

        $fieldMapping = [];
        foreach ($mappingKeys as $k => $excelColumn) {
            $fieldMapping[$excelColumn] = [
                'target' => $validated['mapping_target'][$k] ?? null,
                'confirmed' => $validated['mapping_confirmed'][$k] ?? 'needs_review',
                'note' => $validated['mapping_note'][$k] ?? null,
            ];
        }

        ExcelImportAgreementResponse::create([
            'submitted_by' => auth()->id(),
            'source_file' => $validated['source_file'] ?? null,
            'source_sheet' => $validated['source_sheet'] ?? null,
            'row_represents' => $validated['row_represents'],
            'row_represents_other' => $validated['row_represents_other'] ?? null,
            'officer_dealing_source' => $validated['officer_dealing_source'],
            'officer_dealing_other' => $validated['officer_dealing_other'] ?? null,
            'field_mapping' => $fieldMapping,
            'duplicate_handling' => $validated['duplicate_handling'],
            'duplicate_handling_other' => $validated['duplicate_handling_other'] ?? null,
            'missing_required_policy' => $validated['missing_required_policy'],
            'missing_required_default' => $validated['missing_required_default'] ?? null,
            'date_parsing' => $validated['date_parsing'],
            'date_parsing_other' => $validated['date_parsing_other'] ?? null,
            'text_handling' => $validated['text_handling'],
            'text_handling_other' => $validated['text_handling_other'] ?? null,
            'entered_by_mapping' => $validated['entered_by_mapping'],
            'entered_by_fallback_user' => $validated['entered_by_fallback_user'] ?? null,
            'import_scope' => $validated['import_scope'],
            'import_scope_other' => $validated['import_scope_other'] ?? null,
            'audit_and_rollback' => $validated['audit_and_rollback'],
            'cutover_window' => $validated['cutover_window'],
        ]);

        return redirect()->route('excel-import-agreement.index')->with('success', 'Agreement response submitted successfully.');
    }

    public function index(): View
    {
        $responses = ExcelImportAgreementResponse::query()
            ->with('submitter:id,name,username')
            ->latest()
            ->paginate(20);

        return view('excel_import_agreement.index', compact('responses'));
    }

    public function show(ExcelImportAgreementResponse $response): View
    {
        $response->load('submitter:id,name,username');

        return view('excel_import_agreement.show', compact('response'));
    }
}
