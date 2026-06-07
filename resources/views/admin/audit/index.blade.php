@extends('layouts.app')

@section('title', 'Audit Logs')
@section('page-title', 'Audit Logs')
@section('breadcrumbs', 'Administration / Audit Logs')

@section('actions')
    @can('admin.audit.export')
        <a href="{{ route('admin.audit.export', request()->query()) }}" class="btn btn-outline-primary btn-sm">Export CSV</a>
    @endcan
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.audit.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Search</label>
                <input type="text" name="q" class="form-control form-control-sm" value="{{ request('q') }}" placeholder="Action, actor, route, request ID">
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
            <div class="col-md-2">
                <label class="form-label small">Level</label>
                <select name="level" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach(['info', 'warning', 'error'] as $level)
                        <option value="{{ $level }}" @selected(request('level') === $level)>{{ ucfirst($level) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Outcome</label>
                <select name="outcome" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach(['success', 'failed'] as $outcome)
                        <option value="{{ $outcome }}" @selected(request('outcome') === $outcome)>{{ ucfirst($outcome) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Date From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Date To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ route('admin.audit.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th>Timestamp</th>
                <th>Module</th>
                <th>Action</th>
                <th>Target</th>
                <th>Level</th>
                <th>Outcome</th>
                <th>Actor</th>
                <th>IP</th>
                <th>Request ID</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                    <td>{{ $log->module ?? 'system' }}</td>
                    <td>
                        @php
                            $friendlyAction = str_contains($log->action, '_store') || str_contains($log->action, '.created') ? 'Created record'
                                : (str_contains($log->action, '_update') || str_contains($log->action, '.updated') ? 'Updated record'
                                : (str_contains($log->action, '_destroy') || str_contains($log->action, '.deleted') ? 'Deleted record'
                                : (str_contains($log->action, '.viewed') || str_contains($log->action, '_show') ? 'Viewed record' : 'Performed action')));
                        @endphp
                        <div class="fw-semibold">{{ $friendlyAction }}</div>
                        <div class="text-muted small">{{ $log->action }}</div>
                        <div class="text-muted small">{{ $log->method }} {{ $log->route_name ?? '—' }}</div>
                    </td>
                    <td class="small">
                        @if($log->auditable_type || $log->auditable_id)
                            <div>{{ class_basename((string) $log->auditable_type ?: 'Record') }}</div>
                            <div class="text-muted">{{ $log->auditable_id ?? '—' }}</div>
                        @else
                            —
                        @endif
                    </td>
                    <td><span class="badge {{ $log->level === 'error' ? 'text-bg-danger' : ($log->level === 'warning' ? 'text-bg-warning' : 'text-bg-secondary') }}">{{ $log->level }}</span></td>
                    <td><span class="badge {{ $log->outcome === 'failed' ? 'text-bg-danger' : 'text-bg-success' }}">{{ $log->outcome }}</span></td>
                    <td>{{ $log->user?->name ?? $log->actor_name ?? 'System' }}</td>
                    <td>{{ $log->ip_address ?? '—' }}</td>
                    <td class="small">{{ $log->request_id ?? '—' }}</td>
                    <td class="text-end">
                        <a href="{{ route('admin.audit.show', $log) }}" class="btn btn-sm btn-outline-secondary">View Details</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center text-muted py-4">No audit entries found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
        <div class="card-footer bg-white">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
