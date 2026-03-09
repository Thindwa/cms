@extends('layouts.app')

@section('title', 'Excel Import Agreement Form')
@section('page-title', 'Excel Import Agreement Form')
@section('breadcrumbs', 'Excel Import Agreement / New Form')

@section('actions')
    <a href="{{ route('excel-import-agreement.index') }}" class="btn btn-outline-secondary btn-sm">View Responses</a>
@endsection

@section('content')
<form method="POST" action="{{ route('excel-import-agreement.store') }}">
    @csrf

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white">Source File</div>
        <div class="card-body row g-3">
            <div class="col-md-6">
                <label class="form-label">File</label>
                <input type="text" name="source_file" class="form-control @error('source_file') is-invalid @enderror" value="{{ old('source_file', 'OFFICER DEALING-NO 41.xlsx') }}">
                @error('source_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Sheet</label>
                <input type="text" name="source_sheet" class="form-control @error('source_sheet') is-invalid @enderror" value="{{ old('source_sheet', 'Sheet1') }}">
                @error('source_sheet')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white">Sample Rows Look Like</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>DATE</th>
                            <th>PLAINTIFF</th>
                            <th>REFERNCE NO</th>
                            <th>CAUSE NO</th>
                            <th>LATEST ISSUE</th>
                            <th>FILE MOVED TO</th>
                            <th>ENTERED BY</th>
                            <th>LEGAL OPINION RESPONDANT</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>17/09/2008</td>
                            <td>Mr Charles Kasambala</td>
                            <td>AG/877/8813</td>
                            <td></td>
                            <td>Letter from Crown & Duke</td>
                            <td>1</td>
                            <td>I. Jose</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>42256 (Excel serial)</td>
                            <td>G4S SECURITY SERVICES V MINISTRY OF AGRICULTURE- CROP PRODUCTION</td>
                            <td>AG/877/519/2015</td>
                            <td>BT COM CASE NO 212/2015</td>
                            <td>Long narrative issue text</td>
                            <td>CHISIZA</td>
                            <td>F.M. TEMBO</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="small text-muted mb-0">System fields in use: <code>date_filed</code>, <code>claimant</code>, <code>reference_number</code>, <code>cause_number</code>, <code>description</code>, and required <code>Officer Dealing</code>.</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white">Agreement Questions</div>
        <div class="card-body">
            <div class="mb-4">
                <label class="form-label fw-semibold d-block">Q1. What does one Excel row represent?</label>
                <p class="small text-muted">Example: rows with the same <code>AG/877/8813</code> could be updates to one case, not separate new cases.</p>
                @foreach(['new_case' => 'One new case', 'update_event' => 'An update/event on an existing case', 'other' => 'Other'] as $v => $l)
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="row_represents" id="row_represents_{{ $v }}" value="{{ $v }}" {{ old('row_represents') === $v ? 'checked' : '' }}>
                        <label class="form-check-label" for="row_represents_{{ $v }}">{{ $l }}</label>
                    </div>
                @endforeach
                <input type="text" name="row_represents_other" class="form-control mt-2" placeholder="If Other, specify" value="{{ old('row_represents_other') }}">
                @error('row_represents')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold d-block">Q2. Which value should populate required CMS field "Officer Dealing"?</label>
                <p class="small text-muted">Example: choose <code>FILE MOVED TO</code> if values like <code>CHISIZA</code> represent the officer; choose <code>ENTERED BY</code> if values like <code>F.M. TEMBO</code> are the assigned officer.</p>
                @foreach(['file_moved_to' => 'FILE MOVED TO', 'entered_by' => 'ENTERED BY', 'fixed_default' => 'Fixed default value', 'other' => 'Other mapping'] as $v => $l)
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="officer_dealing_source" id="officer_dealing_source_{{ $v }}" value="{{ $v }}" {{ old('officer_dealing_source') === $v ? 'checked' : '' }}>
                        <label class="form-check-label" for="officer_dealing_source_{{ $v }}">{{ $l }}</label>
                    </div>
                @endforeach
                <input type="text" name="officer_dealing_other" class="form-control mt-2" placeholder="Default value or other mapping details" value="{{ old('officer_dealing_other') }}">
                @error('officer_dealing_source')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold d-block">Q3. Confirm field mapping (enter CMS field or alternative)</label>
                <p class="small text-muted">Example mapping used in the system: <code>DATE -> date_filed</code>, <code>PLAINTIFF -> claimant</code>, <code>REFERNCE NO -> reference_number</code>, <code>LATEST ISSUE -> description</code>.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light"><tr><th>Excel Column</th><th>CMS Mapping / Alternative</th><th>Confirmed?</th><th>Note (optional)</th></tr></thead>
                        <tbody>
                            @php
                                $rows = [
                                    ['key' => 'date', 'excel' => 'DATE', 'default' => 'date_filed'],
                                    ['key' => 'plaintiff', 'excel' => 'PLAINTIFF', 'default' => 'claimant'],
                                    ['key' => 'reference_no', 'excel' => 'REFERNCE NO', 'default' => 'reference_number'],
                                    ['key' => 'cause_no', 'excel' => 'CAUSE NO', 'default' => 'cause_number'],
                                    ['key' => 'latest_issue', 'excel' => 'LATEST ISSUE', 'default' => 'description'],
                                    ['key' => 'file_moved_to', 'excel' => 'FILE MOVED TO', 'default' => 'officer_dealing (if chosen)'],
                                    ['key' => 'entered_by', 'excel' => 'ENTERED BY', 'default' => 'entered_by_legacy'],
                                    ['key' => 'legal_opinion_respondant', 'excel' => 'LEGAL OPINION RESPONDANT', 'default' => 'defendant'],
                                ];
                            @endphp
                            @foreach($rows as $row)
                                <tr>
                                    <td>{{ $row['excel'] }}</td>
                                    <td><input type="text" name="mapping_target[{{ $row['key'] }}]" class="form-control" value="{{ old('mapping_target.' . $row['key'], $row['default']) }}"></td>
                                    <td>
                                        <select name="mapping_confirmed[{{ $row['key'] }}]" class="form-select">
                                            <option value="yes" {{ old('mapping_confirmed.' . $row['key'], 'needs_review') === 'yes' ? 'selected' : '' }}>Yes</option>
                                            <option value="no" {{ old('mapping_confirmed.' . $row['key']) === 'no' ? 'selected' : '' }}>No</option>
                                            <option value="needs_review" {{ old('mapping_confirmed.' . $row['key'], 'needs_review') === 'needs_review' ? 'selected' : '' }}>Needs review</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="mapping_note[{{ $row['key'] }}]" class="form-control" value="{{ old('mapping_note.' . $row['key']) }}" placeholder="e.g. confirm with legal team"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @error('mapping_target')<div class="text-danger small">{{ $message }}</div>@enderror
                @error('mapping_confirmed')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold d-block">Q4. Duplicate handling</label>
                <p class="small text-muted">Example: if <code>AG/877/8813</code> appears on multiple rows, choose whether to create duplicates, update one case, or store later rows as notes.</p>
                @foreach(['create_new' => 'Create another case record', 'update_existing' => 'Update existing case', 'add_note' => 'Add as case note/history entry', 'other' => 'Other'] as $v => $l)
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="duplicate_handling" id="duplicate_handling_{{ $v }}" value="{{ $v }}" {{ old('duplicate_handling') === $v ? 'checked' : '' }}>
                        <label class="form-check-label" for="duplicate_handling_{{ $v }}">{{ $l }}</label>
                    </div>
                @endforeach
                <input type="text" name="duplicate_handling_other" class="form-control mt-2" placeholder="If Other, specify" value="{{ old('duplicate_handling_other') }}">
                @error('duplicate_handling')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold d-block">Q5. Missing required values policy</label>
                <p class="small text-muted">Example: if Officer Dealing is blank after mapping, choose whether to skip row or import with default like <code>Unassigned Officer</code>.</p>
                @foreach(['skip_and_report' => 'Skip row and report error', 'import_with_default' => 'Import with default value', 'manual_review' => 'Hold for manual review queue'] as $v => $l)
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="missing_required_policy" id="missing_required_policy_{{ $v }}" value="{{ $v }}" {{ old('missing_required_policy') === $v ? 'checked' : '' }}>
                        <label class="form-check-label" for="missing_required_policy_{{ $v }}">{{ $l }}</label>
                    </div>
                @endforeach
                <input type="text" name="missing_required_default" class="form-control mt-2" placeholder="Default value (if applicable)" value="{{ old('missing_required_default') }}">
                @error('missing_required_policy')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold d-block">Q6. Date parsing formats accepted</label>
                <p class="small text-muted">Example: accept both <code>17/09/2008</code> and Excel serial values like <code>42256</code>.</p>
                @php $oldDateParsing = old('date_parsing', []); @endphp
                @foreach(['ddmmyyyy_text' => 'dd/mm/yyyy (text)', 'excel_serial' => 'Excel serial numbers', 'both' => 'Both', 'other' => 'Other'] as $v => $l)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="date_parsing[]" id="date_parsing_{{ $v }}" value="{{ $v }}" {{ in_array($v, $oldDateParsing, true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="date_parsing_{{ $v }}">{{ $l }}</label>
                    </div>
                @endforeach
                <input type="text" name="date_parsing_other" class="form-control mt-2" placeholder="If Other, specify" value="{{ old('date_parsing_other') }}">
                @error('date_parsing')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold d-block">Q7. Text handling</label>
                <p class="small text-muted">Example: keep long <code>LATEST ISSUE</code> text as-is, or clean punctuation/spacing before import.</p>
                @foreach(['as_is' => 'Keep legacy text exactly as-is', 'clean_normalise' => 'Clean/normalise (trim whitespace, standardise punctuation)', 'mixed' => 'Mixed approach'] as $v => $l)
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="text_handling" id="text_handling_{{ $v }}" value="{{ $v }}" {{ old('text_handling') === $v ? 'checked' : '' }}>
                        <label class="form-check-label" for="text_handling_{{ $v }}">{{ $l }}</label>
                    </div>
                @endforeach
                <input type="text" name="text_handling_other" class="form-control mt-2" placeholder="If mixed, specify" value="{{ old('text_handling_other') }}">
                @error('text_handling')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold d-block">Q8. ENTERED BY user mapping</label>
                <p class="small text-muted">Example: normalize <code>F.M.TEMBO</code> and <code>F.M. TEMBO</code> to one system user.</p>
                @foreach(['normalise_match' => 'Map by normalisation rules', 'fallback_user' => 'Use fallback user', 'legacy_text_only' => 'Leave as legacy text only'] as $v => $l)
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="entered_by_mapping" id="entered_by_mapping_{{ $v }}" value="{{ $v }}" {{ old('entered_by_mapping') === $v ? 'checked' : '' }}>
                        <label class="form-check-label" for="entered_by_mapping_{{ $v }}">{{ $l }}</label>
                    </div>
                @endforeach
                <input type="text" name="entered_by_fallback_user" class="form-control mt-2" placeholder="Fallback user (if applicable)" value="{{ old('entered_by_fallback_user') }}">
                @error('entered_by_mapping')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold d-block">Q9. Import scope</label>
                <p class="small text-muted">Example: start with <code>Sheet1</code> only, then decide if recurring imports for future files are needed.</p>
                @php $oldScope = old('import_scope', []); @endphp
                @foreach(['one_time_only' => 'One-time migration only', 'recurring' => 'Recurring imports', 'sheet1_only' => 'Import only Sheet1', 'multiple_files' => 'Import multiple sheets/files'] as $v => $l)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="import_scope[]" id="import_scope_{{ $v }}" value="{{ $v }}" {{ in_array($v, $oldScope, true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="import_scope_{{ $v }}">{{ $l }}</label>
                    </div>
                @endforeach
                <input type="text" name="import_scope_other" class="form-control mt-2" placeholder="If multiple files/sheets, specify" value="{{ old('import_scope_other') }}">
                @error('import_scope')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold d-block">Q10. Audit and rollback</label>
                <p class="small text-muted">Example: keep a batch log so you can trace rows created/updated and roll back the whole batch if mapping is wrong.</p>
                @foreach(['require_log_report' => 'Require import log report', 'require_batch_rollback' => 'Require rollback capability per import batch', 'not_required' => 'Not required'] as $v => $l)
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="audit_and_rollback" id="audit_and_rollback_{{ $v }}" value="{{ $v }}" {{ old('audit_and_rollback') === $v ? 'checked' : '' }}>
                        <label class="form-check-label" for="audit_and_rollback_{{ $v }}">{{ $l }}</label>
                    </div>
                @endforeach
                @error('audit_and_rollback')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="mb-2">
                <label class="form-label fw-semibold d-block">Q11. Cutover window during final import</label>
                <p class="small text-muted">Example: freeze edits for one day during final import, or continue editing and schedule a delta import.</p>
                @foreach(['freeze_excel' => 'Freeze Excel edits', 'continue_and_delta' => 'Continue edits and run delta import later'] as $v => $l)
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="cutover_window" id="cutover_window_{{ $v }}" value="{{ $v }}" {{ old('cutover_window') === $v ? 'checked' : '' }}>
                        <label class="form-check-label" for="cutover_window_{{ $v }}">{{ $l }}</label>
                    </div>
                @endforeach
                @error('cutover_window')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Submit Agreement Response</button>
        <a href="{{ route('excel-import-agreement.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
@endsection
