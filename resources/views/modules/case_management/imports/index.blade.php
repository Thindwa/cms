@extends('layouts.app')

@section('title', 'Excel Imports')
@section('breadcrumbs', 'Case Management / Excel Imports')
@section('page-title', 'Excel Import Center')

@section('actions')
    <div class="d-flex gap-2">
        @can('reset', \App\Modules\CaseManagement\Models\CaseImportBatch::class)
            <button type="submit"
                    form="reset-selected-imports-form"
                    class="btn btn-outline-danger"
                    id="reset-selected-btn"
                    disabled>
                Reset Selected Imports
            </button>
        @endcan
        @can('create', \App\Modules\CaseManagement\Models\CaseImportBatch::class)
            <a href="{{ route('cases.imports.create') }}" class="btn btn-outline-primary">Single File Import</a>
        @endcan
        @can('create', \App\Modules\CaseManagement\Models\CaseImportBulkBatch::class)
            <a href="{{ route('cases.imports.bulk.create') }}" class="btn btn-primary">Bulk Import</a>
        @endcan
    </div>
@endsection

@section('content')
<form method="POST"
      id="reset-selected-imports-form"
      action="{{ route('cases.imports.resetSelected') }}"
      data-confirm-title="Reset Selected Imports"
      data-confirm-message="Reset only selected import batches? This will remove selected import history/files and rollback tracked imported records."
      data-confirm-button="Reset Selected">
    @csrf

<div class="card mb-3">
    <div class="card-header">Bulk Import Batches</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px;">@can('reset', \App\Modules\CaseManagement\Models\CaseImportBatch::class)<input type="checkbox" class="form-check-input" id="select-all-bulk">@endcan</th>
                        <th>Created</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th>By</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse(($bulkBatches ?? []) as $bulk)
                    <tr>
                        <td>
                            @can('reset', \App\Modules\CaseManagement\Models\CaseImportBatch::class)
                                <input type="checkbox" class="form-check-input import-select-bulk" name="bulk_batch_ids[]" value="{{ $bulk->id }}">
                            @endcan
                        </td>
                        <td>{{ $bulk->created_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ $bulk->name }}</td>
                        <td><span class="badge bg-secondary">{{ str_replace('_', ' ', $bulk->status) }}</span></td>
                        <td>{{ $bulk->processed_files }}/{{ $bulk->total_files }}</td>
                        <td>{{ $bulk->creator?->name ?? 'System' }}</td>
                        <td class="text-end">
                            @can('view', $bulk)
                                <a href="{{ route('cases.imports.bulk.show', $bulk) }}" class="btn btn-sm btn-outline-primary">Open</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-4 text-muted">No bulk imports yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">Single File Import Batches</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px;">@can('reset', \App\Modules\CaseManagement\Models\CaseImportBatch::class)<input type="checkbox" class="form-check-input" id="select-all-single">@endcan</th>
                        <th>Created</th>
                        <th>File</th>
                        <th>Sheet</th>
                        <th>Status</th>
                        <th>By</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($batches as $batch)
                    <tr>
                        <td>
                            @can('reset', \App\Modules\CaseManagement\Models\CaseImportBatch::class)
                                <input type="checkbox" class="form-check-input import-select-single" name="single_batch_ids[]" value="{{ $batch->id }}">
                            @endcan
                        </td>
                        <td>{{ $batch->created_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ $batch->source_file_name }}</td>
                        <td>{{ $batch->sheet_name }}</td>
                        <td><span class="badge bg-secondary">{{ str_replace('_', ' ', $batch->status) }}</span></td>
                        <td>{{ $batch->creator?->name ?? 'System' }}</td>
                        <td class="text-end">
                            @can('view', $batch)
                                <a href="{{ route('cases.imports.show', $batch) }}" class="btn btn-sm btn-outline-primary">Open</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-4 text-muted">No single-file imports yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($batches->hasPages())
        <div class="card-footer">{{ $batches->links() }}</div>
    @endif
</div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const resetBtn = document.getElementById('reset-selected-btn');
    const singleChecks = Array.from(document.querySelectorAll('.import-select-single'));
    const bulkChecks = Array.from(document.querySelectorAll('.import-select-bulk'));
    const selectAllSingle = document.getElementById('select-all-single');
    const selectAllBulk = document.getElementById('select-all-bulk');

    const allChecks = [...singleChecks, ...bulkChecks];
    const updateButton = () => {
        if (!resetBtn) {
            return;
        }
        const selectedCount = allChecks.filter(c => c.checked).length;
        resetBtn.disabled = selectedCount === 0;
        resetBtn.textContent = selectedCount > 0
            ? `Reset Selected Imports (${selectedCount})`
            : 'Reset Selected Imports';
    };

    const bindSelectAll = (master, checks) => {
        if (!master) return;
        master.addEventListener('change', function () {
            checks.forEach(c => { c.checked = master.checked; });
            updateButton();
        });
    };

    bindSelectAll(selectAllSingle, singleChecks);
    bindSelectAll(selectAllBulk, bulkChecks);
    allChecks.forEach(c => c.addEventListener('change', updateButton));
    updateButton();
})();
</script>
@endpush
