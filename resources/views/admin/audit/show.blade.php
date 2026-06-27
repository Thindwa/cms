@extends('layouts.app')

@section('title', 'Audit Event')
@section('page-title', 'Audit Event Details')
@section('breadcrumbs', 'Administration / Audit Logs / Event')

@section('actions')
    <a href="{{ route('admin.audit.index', request()->except(['auditLog'])) }}" class="btn btn-outline-secondary btn-sm">Back to Audit Logs</a>
@endsection

@push('styles')
<style>
    .audit-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 1rem; }
    .audit-card { border: 0; border-radius: 12px; box-shadow: 0 1px 4px rgba(16,24,40,.08); }
    .audit-k { color: #667085; font-size: .85rem; }
    .audit-v { color: #101828; font-weight: 600; word-break: break-word; }
    .audit-pre { background: #0f172a; color: #e2e8f0; border-radius: 8px; padding: .75rem; max-height: 360px; overflow: auto; font-size: .8rem; line-height: 1.5; }
    .audit-pre .json-key { color: #93c5fd; }
    .audit-pre .json-string { color: #a5d6a7; }
    .audit-pre .json-number { color: #f9a825; }
    .audit-pre .json-bool { color: #ce93d8; }
    .audit-pre .json-null { color: #ef9a9a; }
    .audit-note { border-radius: 10px; border: 1px solid #dbeafe; background: #eff6ff; color: #1e3a8a; padding: .75rem .9rem; }
    .audit-table td { vertical-align: middle; }
    .audit-table td pre { white-space: pre-wrap; margin: 0; font-size: .82rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: .5rem; max-height: 200px; overflow: auto; }
    .change-added { background: #f0fdf4; }
    .change-removed { background: #fef2f2; }
    .change-modified { background: #fffbeb; }
    .badge-outcome { font-size: .75rem; padding: .25em .5em; }
    @media (max-width: 992px) { .audit-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@php
    $context = $auditLog->context ?? [];
    $contextJson = !empty($context) ? json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    $oldJson = !empty($oldValues) ? json_encode($oldValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    $newJson = !empty($newValues) ? json_encode($newValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    $friendlyAction = \App\Http\Controllers\Admin\AuditLogController::friendlyAction($auditLog->action);
@endphp

@section('content')
<div class="audit-note mb-3 d-flex justify-content-between align-items-start">
    <div>
        <div class="fw-semibold">{{ $eventSummary }}</div>
        <div class="small mt-1">{{ $friendlyAction }} &middot; {{ $auditLog->module ?? 'system' }} &middot;
            <span class="badge badge-outcome {{ $auditLog->outcome === 'failed' ? 'text-bg-danger' : 'text-bg-success' }}">{{ $auditLog->outcome }}</span>
            <span class="badge badge-outcome {{ $auditLog->level === 'error' ? 'text-bg-danger' : ($auditLog->level === 'warning' ? 'text-bg-warning' : 'text-bg-secondary') }}">{{ $auditLog->level }}</span>
        </div>
    </div>
    @if($recordUrl)
        <a href="{{ $recordUrl }}" class="btn btn-sm btn-outline-primary text-nowrap">View Record &rarr;</a>
    @endif
</div>

<div class="audit-grid mb-3">
    <div class="card audit-card">
        <div class="card-header bg-white">Event Details</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><div class="audit-k">When</div><div class="audit-v">{{ $auditLog->created_at?->formatDateTime() ?? '—' }}</div></div>
                <div class="col-md-6"><div class="audit-k">Action</div><div class="audit-v">{{ $friendlyAction }} <span class="text-muted fw-normal small">({{ $auditLog->action }})</span></div></div>
                <div class="col-md-6"><div class="audit-k">Module</div><div class="audit-v">{{ $auditLog->module ?? 'system' }}</div></div>
                <div class="col-md-6"><div class="audit-k">Outcome</div><div class="audit-v"><span class="badge {{ $auditLog->outcome === 'failed' ? 'text-bg-danger' : 'text-bg-success' }}">{{ $auditLog->outcome }}</span></div></div>
                <div class="col-md-6"><div class="audit-k">Level</div><div class="audit-v"><span class="badge {{ $auditLog->level === 'error' ? 'text-bg-danger' : ($auditLog->level === 'warning' ? 'text-bg-warning' : 'text-bg-secondary') }}">{{ $auditLog->level }}</span></div></div>
                <div class="col-md-6"><div class="audit-k">Who</div><div class="audit-v">{{ $auditLog->user?->name ?? $auditLog->actor_name ?? 'System' }}</div></div>
                @if($auditLog->actor_email ?? $auditLog->user?->email)
                    <div class="col-md-6"><div class="audit-k">Actor Email</div><div class="audit-v">{{ $auditLog->actor_email ?? $auditLog->user?->email ?? '—' }}</div></div>
                @endif
                <div class="col-md-6"><div class="audit-k">IP Address</div><div class="audit-v">{{ $auditLog->ip_address ?? '—' }}</div></div>
            </div>
        </div>
    </div>

    <div class="card audit-card">
        <div class="card-header bg-white">Affected Record</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><div class="audit-k">Record Type</div>
                    <div class="audit-v">
                        @if($recordUrl)
                            <a href="{{ $recordUrl }}">{{ $auditLog->auditable_type ? class_basename($auditLog->auditable_type) : '—' }}</a>
                        @else
                            {{ $auditLog->auditable_type ? class_basename($auditLog->auditable_type) : '—' }}
                        @endif
                    </div>
                </div>
                <div class="col-md-6"><div class="audit-k">Record ID</div><div class="audit-v">{{ $auditLog->auditable_id ?? '—' }}</div></div>
                <div class="col-md-6"><div class="audit-k">Request Type</div><div class="audit-v">{{ $auditLog->method ?? '—' }}</div></div>
                <div class="col-md-6"><div class="audit-k">Route</div><div class="audit-v">{{ $auditLog->route_name ?? '—' }}</div></div>
                <div class="col-12"><div class="audit-k">URL</div><div class="audit-v" style="font-size:.9rem;">{{ $auditLog->url ?? '—' }}</div></div>
                <div class="col-12"><div class="audit-k">Tracking ID</div><div class="audit-v" style="font-size:.85rem;">{{ $auditLog->request_id ?? '—' }}</div></div>
            </div>
        </div>
    </div>
</div>

<div class="card audit-card mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span>What Changed</span>
        @if(count($changedFields) > 0)
            <span class="badge bg-secondary">{{ count($changedFields) }} field{{ count($changedFields) !== 1 ? 's' : '' }}</span>
        @endif
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0 audit-table">
                <thead class="table-light">
                <tr>
                    <th style="width: 200px;">Field</th>
                    <th style="width: 38%;">Before</th>
                    <th style="width: 38%;">After</th>
                </tr>
                </thead>
                <tbody>
                @forelse($changedFields as $change)
                    @php
                        $rowClass = $change['before'] === '—' ? 'change-added' : ($change['after'] === '—' ? 'change-removed' : 'change-modified');
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td><span class="fw-semibold">{{ $change['field'] }}</span><div class="small text-muted">{{ $change['raw_field'] }}</div></td>
                        <td><pre>{!! $change['before'] !== '—' ? e($change['before']) : '<span class="text-muted">—</span>' !!}</pre></td>
                        <td><pre>{!! $change['after'] !== '—' ? e($change['after']) : '<span class="text-muted">—</span>' !!}</pre></td>
                    </tr>
                @empty
                    @forelse($submittedFields ?? [] as $change)
                        <tr class="change-added">
                            <td><span class="fw-semibold">{{ $change['field'] }}</span><div class="small text-muted">{{ $change['raw_field'] }}</div></td>
                            <td><pre><span class="text-muted">—</span></pre></td>
                            <td><pre>{{ $change['after'] }}</pre></td>
                        </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-muted py-3 px-3">
                            No change values were stored for this event.
                        </td>
                    </tr>
                    @endforelse
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card audit-card">
    <div class="card-header bg-white">Technical Data (For Developers)</div>
    <div class="card-body">
        <div class="accordion" id="auditTechnicalData">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingContext">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseContext" aria-expanded="false" aria-controls="collapseContext">
                        Request Context
                    </button>
                </h2>
                <div id="collapseContext" class="accordion-collapse collapse" aria-labelledby="headingContext" data-bs-parent="#auditTechnicalData">
                    <div class="accordion-body">
                        @if($contextJson)
                            <pre class="audit-pre mb-0"><code>{!! highlight_json($contextJson) !!}</code></pre>
                        @else
                            <p class="text-muted small mb-0">No request context captured.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingValues">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseValues" aria-expanded="false" aria-controls="collapseValues">
                        Raw Value Snapshots
                    </button>
                </h2>
                <div id="collapseValues" class="accordion-collapse collapse" aria-labelledby="headingValues" data-bs-parent="#auditTechnicalData">
                    <div class="accordion-body">
                        <div class="mb-2 fw-semibold small">Old Values</div>
                        @if($oldJson)
                            <pre class="audit-pre mb-3"><code>{!! highlight_json($oldJson) !!}</code></pre>
                        @else
                            <p class="text-muted small">No old values captured.</p>
                        @endif

                        <div class="mb-2 fw-semibold small">New Values</div>
                        @if($newJson)
                            <pre class="audit-pre mb-0"><code>{!! highlight_json($newJson) !!}</code></pre>
                        @else
                            <p class="text-muted small mb-0">No new values captured.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
