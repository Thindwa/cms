@extends('layouts.app')

@section('title', 'Audit Logs')
@section('page-title', 'Audit Logs')
@section('breadcrumbs', 'Administration / Audit Logs')

@push('styles')
<style>
    .stat-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1rem; text-align: center; }
    .stat-card .stat-number { font-size: 1.5rem; font-weight: 700; color: #0f172a; }
    .stat-card .stat-label { font-size: .8rem; color: #64748b; }
    .period-btn { font-size: .8rem; }
    .period-btn.active { background: #0d6efd; color: #fff; border-color: #0d6efd; }
</style>
@endpush

@section('actions')
    @can('admin.audit.export')
        <a href="{{ route('admin.audit.export', request()->query()) }}" class="btn btn-outline-primary btn-sm">CSV</a>
        <a href="{{ route('admin.audit.export-xlsx', request()->query()) }}" class="btn btn-outline-primary btn-sm">XLSX</a>
    @endcan
@endsection

@section('content')
<div class="row g-2 mb-3" id="auditStats">
    <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-number" id="statTotal">—</div><div class="stat-label">Total</div></div></div>
    <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-number" id="statErrors">—</div><div class="stat-label">Errors</div></div></div>
    <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-number" id="statFailed">—</div><div class="stat-label">Failed</div></div></div>
    <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-number" id="statInfo">—</div><div class="stat-label">Info</div></div></div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.audit.index') }}" class="row g-2 align-items-end">
            <div class="col-12">
                <div class="btn-group btn-group-sm flex-wrap" role="group">
                    <a href="{{ route('admin.audit.index', array_merge(request()->except(['period', 'page']), ['period' => 'today'])) }}" class="btn btn-outline-secondary period-btn @if(request('period') === 'today') active @endif">Today</a>
                    <a href="{{ route('admin.audit.index', array_merge(request()->except(['period', 'page']), ['period' => 'yesterday'])) }}" class="btn btn-outline-secondary period-btn @if(request('period') === 'yesterday') active @endif">Yesterday</a>
                    <a href="{{ route('admin.audit.index', array_merge(request()->except(['period', 'page']), ['period' => 'last_7_days'])) }}" class="btn btn-outline-secondary period-btn @if(request('period') === 'last_7_days') active @endif">7 Days</a>
                    <a href="{{ route('admin.audit.index', array_merge(request()->except(['period', 'page']), ['period' => 'this_month'])) }}" class="btn btn-outline-secondary period-btn @if(request('period') === 'this_month') active @endif">This Month</a>
                    <a href="{{ route('admin.audit.index', array_merge(request()->except(['period', 'page']), ['period' => 'last_30_days'])) }}" class="btn btn-outline-secondary period-btn @if(request('period') === 'last_30_days') active @endif">30 Days</a>
                    <a href="{{ route('admin.audit.index', array_merge(request()->except(['period', 'page']), ['period' => 'this_year'])) }}" class="btn btn-outline-secondary period-btn @if(request('period') === 'this_year') active @endif">This Year</a>
                    @if(request('period'))
                        <a href="{{ route('admin.audit.index', request()->except(['period', 'page'])) }}" class="btn btn-outline-secondary period-btn">Clear</a>
                    @endif
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Search</label>
                <input type="text" name="q" class="form-control form-control-sm" value="{{ request('q') }}" placeholder="Action, actor, route, request ID, values...">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Category</label>
                <select name="action_category" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach(['http' => 'HTTP', 'auth' => 'Authentication', 'case' => 'Case Management', 'category' => 'Categories', 'document' => 'Documents', 'note' => 'Notes', 'import' => 'Imports', 'user' => 'Users', 'role' => 'Roles', 'setting' => 'Settings'] as $catVal => $catLabel)
                        <option value="{{ $catVal }}" @selected(request('action_category') === $catVal)>{{ $catLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Module</label>
                <select name="module" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($modules as $module)
                        <option value="{{ $module }}" @selected(request('module') === $module)>{{ $module }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Action</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label small">Level</label>
                <select name="level" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach(['info', 'warning', 'error'] as $level)
                        <option value="{{ $level }}" @selected(request('level') === $level)>{{ ucfirst($level) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label small">Outcome</label>
                <select name="outcome" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach(['success', 'failed'] as $outcome)
                        <option value="{{ $outcome }}" @selected(request('outcome') === $outcome)>{{ ucfirst($outcome) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Record Type</label>
                <select name="auditable_type" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($auditableTypes as $type)
                        <option value="{{ $type }}" @selected(request('auditable_type') === $type)>{{ class_basename($type) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Record ID</label>
                <input type="text" name="auditable_id" class="form-control form-control-sm" value="{{ request('auditable_id') }}" placeholder="e.g. UUID">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Date From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Date To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ route('admin.audit.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                @can('admin.audit.delete')
                    <button type="submit" form="batchDeleteForm" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete all audit logs matching current filters? This cannot be undone.')">Delete Filtered</button>
                @endcan
            </div>
        </form>
        @can('admin.audit.delete')
            <form id="batchDeleteForm" method="POST" action="{{ route('admin.audit.batch-delete') }}">
                @csrf @method('DELETE')
                @foreach(request()->except(['_token', '_method']) as $key => $value)
                    @if(is_array($value))
                        @foreach($value as $v)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
            </form>
        @endcan
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th>Timestamp</th>
                <th>Action</th>
                <th>Target</th>
                <th>Level</th>
                <th>Outcome</th>
                <th>Actor</th>
                <th>IP / Request ID</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td class="text-nowrap">{{ $log->created_at?->formatDateTime() }}</td>
                    <td>
                        <div class="fw-semibold">{{ \App\Http\Controllers\Admin\AuditLogController::friendlyAction($log->action) }}</div>
                        <div class="text-muted small">{{ $log->action }}</div>
                        <div class="text-muted small">{{ $log->method }} {{ $log->route_name ?? '—' }}</div>
                    </td>
                    <td class="small">
                        @if($log->auditable_type || $log->auditable_id)
                            <div>{{ class_basename((string) $log->auditable_type ?: 'Record') }}</div>
                            <div class="text-muted">
                                @php
                                    $recordUrl = $log->auditable_type === \App\Modules\CaseManagement\Models\CaseModel::class && $log->auditable_id
                                        ? route('cases.show', $log->auditable_id, false) : null;
                                @endphp
                                @if($recordUrl)
                                    <a href="{{ $recordUrl }}">{{ $log->auditable_id ?? '—' }}</a>
                                @else
                                    {{ $log->auditable_id ?? '—' }}
                                @endif
                            </div>
                        @else
                            —
                        @endif
                    </td>
                    <td><span class="badge {{ $log->level === 'error' ? 'text-bg-danger' : ($log->level === 'warning' ? 'text-bg-warning' : 'text-bg-secondary') }}">{{ $log->level }}</span></td>
                    <td><span class="badge {{ $log->outcome === 'failed' ? 'text-bg-danger' : 'text-bg-success' }}">{{ $log->outcome }}</span></td>
                    <td>{{ $log->user?->name ?? $log->actor_name ?? 'System' }}</td>
                    <td class="small">
                        <div>{{ $log->ip_address ?? '—' }}</div>
                        <div class="text-muted" title="{{ $log->request_id ?? '' }}">{{ Str::limit($log->request_id ?? '—', 12) }}</div>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.audit.show', $log) }}" class="btn btn-sm btn-outline-secondary">View</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No audit entries found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
        <div class="card-footer bg-white">{{ $logs->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
const params = new URLSearchParams(window.location.search);
const queryStr = params.toString();
fetch('{{ route('admin.audit.stats') }}' + (queryStr ? '?' + queryStr : ''))
    .then(r => r.json())
    .then(data => {
        document.getElementById('statTotal').textContent = data.total ?? 0;
        document.getElementById('statErrors').textContent = data.by_level?.error ?? 0;
        document.getElementById('statFailed').textContent = data.by_outcome?.failed ?? 0;
        document.getElementById('statInfo').textContent = data.by_level?.info ?? 0;
    })
    .catch(() => {});
</script>
@endpush
