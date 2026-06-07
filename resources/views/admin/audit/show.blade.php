@extends('layouts.app')

@section('title', 'Audit Event')
@section('page-title', 'Audit Event Details')
@section('breadcrumbs', 'Administration / Audit Logs / Event')

@section('actions')
    <a href="{{ route('admin.audit.index', request()->query()) }}" class="btn btn-outline-secondary btn-sm">Back to Audit Logs</a>
@endsection

@push('styles')
<style>
    .audit-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 1rem; }
    .audit-card { border: 0; border-radius: 12px; box-shadow: 0 1px 4px rgba(16,24,40,.08); }
    .audit-k { color: #667085; font-size: .85rem; }
    .audit-v { color: #101828; font-weight: 600; word-break: break-word; }
    .audit-pre { background: #0f172a; color: #e2e8f0; border-radius: 8px; padding: .75rem; max-height: 360px; overflow: auto; font-size: .8rem; }
    .audit-note { border-radius: 10px; border: 1px solid #dbeafe; background: #eff6ff; color: #1e3a8a; padding: .75rem .9rem; }
    .audit-table td pre { white-space: pre-wrap; margin: 0; font-size: .82rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: .5rem; }
    @media (max-width: 992px) { .audit-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@php
    $context = $auditLog->context ?? [];
    $contextJson = !empty($context) ? json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    $oldJson = !empty($oldValues) ? json_encode($oldValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    $newJson = !empty($newValues) ? json_encode($newValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
@endphp

@section('content')
<div class="audit-note mb-3">
    <div class="fw-semibold">What happened</div>
    <div>{{ $eventSummary }}</div>
</div>

<div class="audit-grid mb-3">
    <div class="card audit-card">
        <div class="card-header bg-white">Event Details</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><div class="audit-k">When</div><div class="audit-v">{{ $auditLog->created_at?->format('Y-m-d H:i:s') ?? '—' }}</div></div>
                <div class="col-md-6"><div class="audit-k">Action</div><div class="audit-v">{{ $auditLog->action }}</div></div>
                <div class="col-md-6"><div class="audit-k">Module</div><div class="audit-v">{{ $auditLog->module ?? 'system' }}</div></div>
                <div class="col-md-6"><div class="audit-k">Outcome</div><div class="audit-v">{{ $auditLog->outcome }}</div></div>
                <div class="col-md-6"><div class="audit-k">Level</div><div class="audit-v">{{ $auditLog->level }}</div></div>
                <div class="col-md-6"><div class="audit-k">Who</div><div class="audit-v">{{ $auditLog->user?->name ?? $auditLog->actor_name ?? 'System' }}</div></div>
                <div class="col-md-6"><div class="audit-k">Actor Email</div><div class="audit-v">{{ $auditLog->actor_email ?? $auditLog->user?->email ?? '—' }}</div></div>
                <div class="col-md-6"><div class="audit-k">IP Address</div><div class="audit-v">{{ $auditLog->ip_address ?? '—' }}</div></div>
            </div>
        </div>
    </div>

    <div class="card audit-card">
        <div class="card-header bg-white">Affected Record</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><div class="audit-k">Record Type</div><div class="audit-v">{{ $auditLog->auditable_type ? class_basename($auditLog->auditable_type) : '—' }}</div></div>
                <div class="col-md-6"><div class="audit-k">Record ID</div><div class="audit-v">{{ $auditLog->auditable_id ?? '—' }}</div></div>
                <div class="col-md-6"><div class="audit-k">Request Type</div><div class="audit-v">{{ $auditLog->method ?? '—' }}</div></div>
                <div class="col-md-6"><div class="audit-k">Route</div><div class="audit-v">{{ $auditLog->route_name ?? '—' }}</div></div>
                <div class="col-md-12"><div class="audit-k">URL</div><div class="audit-v">{{ $auditLog->url ?? '—' }}</div></div>
                <div class="col-md-12"><div class="audit-k">Tracking ID</div><div class="audit-v">{{ $auditLog->request_id ?? '—' }}</div></div>
            </div>
        </div>
    </div>
</div>

<div class="card audit-card mb-3">
    <div class="card-header bg-white">What Changed</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0 audit-table">
                <thead class="table-light">
                <tr>
                    <th style="width: 220px;">Field</th>
                    <th>Before</th>
                    <th>After</th>
                </tr>
                </thead>
                <tbody>
                @forelse($changedFields as $change)
                    <tr>
                        <td><span class="fw-semibold">{{ $change['field'] }}</span><div class="small text-muted">{{ $change['raw_field'] }}</div></td>
                        <td><pre>{{ $change['before'] }}</pre></td>
                        <td><pre>{{ $change['after'] }}</pre></td>
                    </tr>
                @empty
                    @forelse($submittedFields ?? [] as $change)
                        <tr>
                            <td><span class="fw-semibold">{{ $change['field'] }}</span><div class="small text-muted">{{ $change['raw_field'] }}</div></td>
                            <td><pre>{{ $change['before'] }}</pre></td>
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
                            <pre class="audit-pre mb-0">{{ $contextJson }}</pre>
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
                            <pre class="audit-pre mb-3">{{ $oldJson }}</pre>
                        @else
                            <p class="text-muted small">No old values captured.</p>
                        @endif

                        <div class="mb-2 fw-semibold small">New Values</div>
                        @if($newJson)
                            <pre class="audit-pre mb-0">{{ $newJson }}</pre>
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
