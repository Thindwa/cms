@extends('layouts.app')

@section('title', 'Import Review')
@section('breadcrumbs', 'Case Management / Excel Imports / Review')
@section('page-title', 'Import Review: ' . $batch->source_file_name)

@section('actions')
    <a href="{{ route('cases.imports.index') }}" class="btn btn-outline-secondary">Back to Batches</a>
@endsection

@section('content')
<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-muted">Status</div><div class="fw-semibold">{{ str_replace('_', ' ', $batch->status) }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-muted">Rows Processed</div><div class="fw-semibold">{{ $analysis['stats']['rows_processed'] ?? 0 }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-muted">Blocking Issues</div><div class="fw-semibold text-danger">{{ $analysis['stats']['blocking_issues'] ?? 0 }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-muted">Warnings</div><div class="fw-semibold text-warning">{{ $analysis['stats']['warning_issues'] ?? 0 }}</div></div></div></div>
</div>

<div class="card mb-3">
    <div class="card-header">1) Resolve Mapping and Policies</div>
    <div class="card-body">
        @can('execute', $batch)
        <form method="POST" action="{{ route('cases.imports.reanalyze', $batch) }}">
            @csrf
            @method('PUT')

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Sheet</label>
                    <select name="sheet_name" class="form-select">
                        @foreach(($profile['sheet_names'] ?? [$batch->sheet_name]) as $sheet)
                            <option value="{{ $sheet }}" @selected($sheet === $batch->sheet_name)>{{ $sheet }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Duplicate Policy</label>
                    <select name="options[duplicate_policy]" class="form-select">
                        <option value="update_existing" @selected(($options['duplicate_policy'] ?? '') === 'update_existing')>Update existing case</option>
                        <option value="create_new" @selected(($options['duplicate_policy'] ?? '') === 'create_new')>Always create new case</option>
                        <option value="add_note_only" @selected(($options['duplicate_policy'] ?? '') === 'add_note_only')>Add note only when matched</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Missing Officer Policy</label>
                    <select name="options[missing_officer_policy]" class="form-select">
                        <option value="default" @selected(($options['missing_officer_policy'] ?? '') === 'default')>Use default officer</option>
                        <option value="skip" @selected(($options['missing_officer_policy'] ?? '') === 'skip')>Skip affected rows</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Default Officer Value</label>
                    <input type="text" class="form-control" name="options[default_officer_value]" value="{{ $options['default_officer_value'] ?? 'Unassigned Officer' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date Parsing</label>
                    <select name="options[date_mode]" class="form-select">
                        <option value="auto" @selected(($options['date_mode'] ?? '') === 'auto')>Auto</option>
                        <option value="ddmmyyyy" @selected(($options['date_mode'] ?? '') === 'ddmmyyyy')>dd/mm/yyyy</option>
                        <option value="excel_serial" @selected(($options['date_mode'] ?? '') === 'excel_serial')>Excel serial only</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Text Policy</label>
                    <select name="options[text_policy]" class="form-select">
                        <option value="clean" @selected(($options['text_policy'] ?? '') === 'clean')>Clean and normalize</option>
                        <option value="raw" @selected(($options['text_policy'] ?? '') === 'raw')>Keep raw spacing</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive mb-3">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>CMS Field</th>
                            <th>Excel Column Mapping</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($mappingFields as $field => $label)
                        <tr>
                            <td>{{ $label }}</td>
                            <td>
                                <select name="mapping[{{ $field }}]" class="form-select form-select-sm">
                                    <option value="">-- Unmapped --</option>
                                    @foreach(($profile['headers'] ?? []) as $header)
                                        <option value="{{ $header }}" @selected(($batch->mapping[$field] ?? null) === $header)>{{ $header }}</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <button class="btn btn-primary">Re-analyze</button>
        </form>
        @else
            <p class="text-muted mb-0">You do not have permission to modify mapping/policies.</p>
        @endcan
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">2) Detected Issues</div>
    <div class="card-body">
        @if(($analysis['issues'] ?? []) === [])
            <p class="text-muted mb-0">No issues detected in sampled analysis.</p>
        @else
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Severity</th>
                            <th>Row</th>
                            <th>Type</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($analysis['issues'] as $issue)
                        <tr>
                            <td>
                                <span class="badge {{ ($issue['severity'] ?? '') === 'blocking' ? 'bg-danger' : 'bg-warning text-dark' }}">{{ $issue['severity'] ?? 'warning' }}</span>
                            </td>
                            <td>{{ $issue['row'] ?? 'N/A' }}</td>
                            <td>{{ $issue['type'] ?? '-' }}</td>
                            <td>{{ $issue['message'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">3) Dry-Run and Execute</div>
    <div class="card-body d-flex gap-2">
        @php
            $rollbackPayload = $batch->import_report['rollback'] ?? null;
            $hasRollbackPayload = is_array($rollbackPayload)
                && (
                    !empty($rollbackPayload['created_case_ids'] ?? [])
                    || !empty($rollbackPayload['updated_cases'] ?? [])
                    || !empty($rollbackPayload['created_note_ids'] ?? [])
                );
            $alreadyImported = $batch->status === 'imported' && !empty($batch->import_report);
        @endphp

        @can('execute', $batch)
            <form method="POST" action="{{ route('cases.imports.dryRun', $batch) }}">
                @csrf
                <button class="btn btn-outline-primary">Run Dry-Run</button>
            </form>
        @endcan

        @can('execute', $batch)
        @if(!$alreadyImported)
            <form method="POST" action="{{ route('cases.imports.execute', $batch) }}"
                  id="execute-import-form">
                @csrf
                <button class="btn btn-success" {{ (($analysis['stats']['blocking_issues'] ?? 0) > 0) ? 'disabled' : '' }}>Execute Final Import</button>
            </form>
        @else
            <button class="btn btn-success" disabled>Already Imported</button>
        @endif

        @endcan

        @can('rollback', $batch)
        @if($alreadyImported && (($batch->import_report['rollback_meta'] ?? null) === null) && $hasRollbackPayload)
        <form method="POST" action="{{ route('cases.imports.rollback', $batch) }}"
              data-confirm-title="Rollback Import Batch"
              data-confirm-message="Rollback this import batch? This will remove created records and revert updates from this batch."
              data-confirm-button="Rollback">
            @csrf
            <button class="btn btn-outline-danger">Rollback Import</button>
        </form>
        @endif
        @endcan
    </div>
    @if(!empty($batch->import_report) && (($batch->import_report['rollback_meta'] ?? null) === null) && !$hasRollbackPayload)
        <div class="card-footer text-muted small">
            Rollback unavailable for this batch because rollback tracking data was not captured when this import was executed.
        </div>
    @endif
    @if($alreadyImported)
        <div class="card-footer text-muted small">
            Final import is locked for this batch to prevent accidental duplicate import.
        </div>
    @endif
</div>

<div class="card mb-3 d-none" id="singleImportProgressCard">
    <div class="card-header">Final Import Progress</div>
    <div class="card-body">
        <div class="progress" style="height: 20px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated" id="singleImportProgressBar" role="progressbar" style="width: 5%;">5%</div>
        </div>
        <div class="small text-muted mt-2" id="singleImportProgressText">Preparing import...</div>
    </div>
</div>

@if(!empty($batch->dry_run_report))
<div class="card mb-3">
    <div class="card-header">Dry-Run Report</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3"><div class="small text-muted">Rows Processed</div><div>{{ $batch->dry_run_report['rows_processed'] ?? 0 }}</div></div>
            <div class="col-md-3"><div class="small text-muted">Cases Created (estimate)</div><div>{{ $batch->dry_run_report['cases_created'] ?? 0 }}</div></div>
            <div class="col-md-3"><div class="small text-muted">Cases Matched (estimate)</div><div>{{ $batch->dry_run_report['cases_matched'] ?? 0 }}</div></div>
            <div class="col-md-3"><div class="small text-muted">Errors</div><div>{{ count($batch->dry_run_report['errors'] ?? []) }}</div></div>
        </div>
    </div>
</div>
@endif

@if(!empty($batch->import_report))
<div class="card">
    <div class="card-header">Final Import Report</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3"><div class="small text-muted">Rows Processed</div><div>{{ $batch->import_report['rows_processed'] ?? 0 }}</div></div>
            <div class="col-md-3"><div class="small text-muted">Cases Created</div><div>{{ $batch->import_report['cases_created'] ?? 0 }}</div></div>
            <div class="col-md-3"><div class="small text-muted">Cases Updated</div><div>{{ $batch->import_report['cases_updated'] ?? 0 }}</div></div>
            <div class="col-md-3"><div class="small text-muted">Errors</div><div>{{ count($batch->import_report['errors'] ?? []) }}</div></div>
        </div>
        @if(!empty($batch->import_report['errors']))
            <hr>
            <h6>Import Errors</h6>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead class="table-light"><tr><th>Row</th><th>Message</th></tr></thead>
                    <tbody>
                        @foreach($batch->import_report['errors'] as $error)
                            <tr><td>{{ $error['row'] ?? '-' }}</td><td>{{ $error['message'] ?? '-' }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if(!empty($batch->import_report['rollback_meta']))
            <hr>
            <h6>Rollback Details</h6>
            <div class="row g-3">
                <div class="col-md-3"><div class="small text-muted">Rolled Back At</div><div>{{ $batch->import_report['rollback_meta']['rolled_back_at'] ?? '-' }}</div></div>
                <div class="col-md-3"><div class="small text-muted">Created Cases Removed</div><div>{{ $batch->import_report['rollback_meta']['created_cases_removed'] ?? 0 }}</div></div>
                <div class="col-md-3"><div class="small text-muted">Updated Cases Restored</div><div>{{ $batch->import_report['rollback_meta']['updated_cases_restored'] ?? 0 }}</div></div>
                <div class="col-md-3"><div class="small text-muted">Notes Removed</div><div>{{ $batch->import_report['rollback_meta']['notes_removed'] ?? 0 }}</div></div>
            </div>
        @endif
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('execute-import-form');
    if (!form) {
        return;
    }

    const progressCard = document.getElementById('singleImportProgressCard');
    const progressBar = document.getElementById('singleImportProgressBar');
    const progressText = document.getElementById('singleImportProgressText');
    let timer = null;
    let current = 5;

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (form.dataset.ajaxSubmitting === '1') {
            return;
        }

        if (!confirm('Run final import now? This will write changes to case records.')) {
            return;
        }

        form.dataset.ajaxSubmitting = '1';

        progressCard.classList.remove('d-none');
        progressText.textContent = 'Import is running...';
        progressBar.style.width = current + '%';
        progressBar.textContent = current + '%';

        timer = setInterval(function () {
            current = Math.min(90, current + 3);
            progressBar.style.width = current + '%';
            progressBar.textContent = current + '%';
        }, 350);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(form),
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok || data.ok === false) {
                throw new Error(data.message || 'Import failed.');
            }

            clearInterval(timer);
            progressBar.classList.remove('progress-bar-animated');
            progressBar.style.width = '100%';
            progressBar.textContent = '100%';
            progressText.textContent = 'Import completed. Redirecting to case list...';

            setTimeout(function () {
                window.location.href = data.redirect_url || '{{ route('cases.index') }}';
            }, 500);
        } catch (error) {
            clearInterval(timer);
            form.dataset.ajaxSubmitting = '0';
            progressBar.classList.remove('progress-bar-animated');
            progressBar.classList.add('bg-danger');
            progressText.textContent = error.message || 'Import failed.';
        }
    });
})();
</script>
@endpush
