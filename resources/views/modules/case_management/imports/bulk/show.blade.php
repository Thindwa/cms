@extends('layouts.app')

@section('title', 'Bulk Import Review')
@section('breadcrumbs', 'Case Management / Excel Imports / Bulk Review')
@section('page-title', 'Bulk Batch: ' . $batch->name)

@section('actions')
    <a href="{{ route('cases.imports.index') }}" class="btn btn-outline-secondary">Back</a>
@endsection

@section('content')
<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-muted">Status</div><div id="statusText" class="fw-semibold">{{ str_replace('_', ' ', $batch->status) }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-muted">Files</div><div id="filesText" class="fw-semibold">{{ $batch->processed_files }}/{{ $batch->total_files }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-muted">Successful</div><div id="successText" class="fw-semibold text-success">{{ $batch->successful_files }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-muted">Failed</div><div id="failedText" class="fw-semibold text-danger">{{ $batch->failed_files }}</div></div></div></div>
</div>

<div class="card mb-3">
    <div class="card-header">Progress</div>
    <div class="card-body">
        <div class="progress" style="height: 22px;">
            <div id="progressBar" class="progress-bar progress-bar-striped" role="progressbar" style="width: {{ $batch->total_files > 0 ? floor(($batch->processed_files / $batch->total_files) * 100) : 0 }}%;">
                <span id="progressText">{{ $batch->total_files > 0 ? floor(($batch->processed_files / $batch->total_files) * 100) : 0 }}%</span>
            </div>
        </div>
        <div class="small text-muted mt-2">Live updates every 2 seconds while processing.</div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">1) Mapping and Policies (Applied to all files)</div>
    <div class="card-body">
        <form method="POST" action="{{ route('cases.imports.bulk.reanalyze', $batch) }}">
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
            </div>
            <div class="row g-3 mb-3">
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
                    <thead class="table-light"><tr><th>CMS Field</th><th>Excel Column</th></tr></thead>
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
            <button class="btn btn-outline-primary">Re-analyze</button>
        </form>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">2) Start Import</div>
    <div class="card-body d-flex gap-2">
        @php
            $pendingCount = $batch->files()->where('status', 'pending')->count();
            $failedCount = $batch->files()->where('status', 'failed')->count();
            $canStart = ($analysis['stats']['blocking_issues'] ?? 0) === 0
                && !in_array($batch->status, ['processing', 'completed', 'rolled_back'], true)
                && (($pendingCount + $failedCount) > 0);
        @endphp
        <form method="POST" action="{{ route('cases.imports.bulk.start', $batch) }}"
              data-confirm-title="Start Bulk Import"
              data-confirm-message="Start processing this bulk import batch now?"
              data-confirm-button="Start Import">
            @csrf
            <button class="btn btn-success" {{ $canStart ? '' : 'disabled' }}>Start Queue Processing</button>
        </form>
        <span class="small text-muted align-self-center">
            Blocking issues: {{ $analysis['stats']['blocking_issues'] ?? 0 }} |
            Pending: {{ $pendingCount }} |
            Failed: {{ $failedCount }}
        </span>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">Detected Issues (from sample file)</div>
    <div class="card-body">
        @if(($analysis['issues'] ?? []) === [])
            <p class="text-muted mb-0">No issues detected.</p>
        @else
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead class="table-light"><tr><th>Severity</th><th>Row</th><th>Type</th><th>Message</th></tr></thead>
                    <tbody>
                        @foreach($analysis['issues'] as $issue)
                            <tr>
                                <td><span class="badge {{ ($issue['severity'] ?? '') === 'blocking' ? 'bg-danger' : 'bg-warning text-dark' }}">{{ $issue['severity'] ?? 'warning' }}</span></td>
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

<div class="card">
    <div class="card-header">Files in Batch</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light"><tr><th>File</th><th>Status</th><th>Error</th></tr></thead>
                <tbody>
                @foreach($files as $file)
                    <tr>
                        <td>{{ $file->source_file_name }}</td>
                        <td><span class="badge bg-secondary">{{ $file->status }}</span></td>
                        <td class="small text-danger">{{ $file->error_message }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @if($files->hasPages())<div class="card-footer">{{ $files->links() }}</div>@endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    const progressUrl = @json(route('cases.imports.bulk.progress', $batch));
    const statusText = document.getElementById('statusText');
    const filesText = document.getElementById('filesText');
    const successText = document.getElementById('successText');
    const failedText = document.getElementById('failedText');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');

    const update = () => {
        fetch(progressUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                statusText.textContent = String(data.status || '').replaceAll('_', ' ');
                filesText.textContent = `${data.processed_files}/${data.total_files}`;
                successText.textContent = data.successful_files;
                failedText.textContent = data.failed_files;
                const p = Math.max(0, Math.min(100, Number(data.percent || 0)));
                progressBar.style.width = `${p}%`;
                progressText.textContent = `${p}%`;

                if (data.done) {
                    progressBar.classList.remove('progress-bar-striped');
                    return;
                }

                setTimeout(update, 2000);
            })
            .catch(() => setTimeout(update, 4000));
    };

    setTimeout(update, 1200);
})();
</script>
@endpush
