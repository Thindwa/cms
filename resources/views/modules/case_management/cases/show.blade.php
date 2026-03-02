@extends('layouts.app')

@section('title', 'Case ' . $case->case_number)
@section('page-title', 'Case ' . $case->case_number)
@section('breadcrumbs', 'Case Management / ' . $case->case_number)

@section('actions')
    @can('cases.edit')
        <a href="{{ route('cases.edit', $case) }}" class="btn btn-primary btn-sm">Edit</a>
    @endcan
@endsection

@section('content')
<div class="d-flex align-items-center gap-2 mb-3">
    <span class="badge {{ $case->status === 'open' ? 'bg-info' : ($case->status === 'in_progress' ? 'bg-warning text-dark' : 'bg-secondary') }}">
        {{ ucfirst(str_replace('_', ' ', $case->status)) }}
    </span>
    <span class="text-muted">{{ $case->title }}</span>
</div>

@php $activeTab = session('tab', 'overview'); @endphp
<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><a class="nav-link {{ $activeTab === 'overview' ? 'active' : '' }}" data-bs-toggle="tab" href="#overview">Overview</a></li>
    <li class="nav-item"><a class="nav-link {{ $activeTab === 'documents' ? 'active' : '' }}" data-bs-toggle="tab" href="#documents">Documents</a></li>
    <li class="nav-item"><a class="nav-link {{ $activeTab === 'notes' ? 'active' : '' }}" data-bs-toggle="tab" href="#notes">Notes</a></li>
    <li class="nav-item"><a class="nav-link {{ $activeTab === 'history' ? 'active' : '' }}" data-bs-toggle="tab" href="#history">History</a></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show {{ $activeTab === 'overview' ? 'active' : '' }}" id="overview">
        <div class="row g-3">
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr><th class="text-muted" style="width:40%">Serial Number</th><td>{{ $case->case_number }}</td></tr>
                    <tr><th class="text-muted">Date Filed</th><td>{{ $case->date_filed?->format('Y-m-d') ?? '—' }}</td></tr>
                    <tr><th class="text-muted">Reference Number</th><td>{{ $case->reference_number ?? '—' }}</td></tr>
                    <tr><th class="text-muted">Defendant</th><td>{{ $case->defendant ?? '—' }}</td></tr>
                    <tr><th class="text-muted">Nature of Claim</th><td>{{ $case->nature_of_claim ?? '—' }}</td></tr>
                    <tr><th class="text-muted">Claimant</th><td>{{ $case->claimant ?? '—' }}</td></tr>
                    <tr><th class="text-muted">Cause Number</th><td>{{ $case->cause_number ?? '—' }}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr><th class="text-muted" style="width:40%">Officer Dealing</th><td>{{ $case->assignedOfficer?->name ?? '—' }}</td></tr>
                    <tr><th class="text-muted">Entered By</th><td>{{ $case->createdByUser?->name ?? '—' }}</td></tr>
                    <tr><th class="text-muted">Priority</th><td>{{ ucfirst($case->priority) }}</td></tr>
                    <tr><th class="text-muted">Created</th><td>{{ $case->created_at->format('Y-m-d H:i') }}</td></tr>
                    <tr><th class="text-muted">Last updated</th><td>{{ $case->updated_at->format('Y-m-d H:i') }}</td></tr>
                </table>
                @if($case->description)
                    <p class="small text-muted mb-1">Description</p>
                    <p class="small">{{ $case->description }}</p>
                @endif
            </div>
        </div>
    </div>
    <div class="tab-pane fade {{ $activeTab === 'documents' ? 'show active' : '' }}" id="documents">
        @can('cases.edit')
        <form action="{{ route('cases.documents.store', $case) }}" method="POST" enctype="multipart/form-data" class="mb-3">
            @csrf
            <div class="input-group">
                <input type="file" name="document" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif" required>
                <button type="submit" class="btn btn-primary btn-sm">Upload</button>
            </div>
            <small class="text-muted">PDF, Word, Excel, images. Max 10MB.</small>
        </form>
        @endcan
        <table class="table table-sm">
            <thead><tr><th>File name</th><th>Version</th><th>Type</th><th>Uploaded by</th><th>Date</th><th></th></tr></thead>
            <tbody>
                @forelse($case->documents->sortBy(['original_name', 'version']) as $doc)
                    <tr>
                        <td>{{ $doc->original_name }}</td>
                        <td>v{{ $doc->version }}</td>
                        <td>{{ $doc->mime_type ?? '—' }}</td>
                        <td>{{ $doc->uploader?->name ?? '—' }}</td>
                        <td>{{ $doc->created_at->format('Y-m-d H:i') }}</td>
                        <td><a href="{{ route('cases.documents.download', [$case, $doc]) }}" class="btn btn-sm btn-outline-secondary">Download</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">No documents yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="tab-pane fade {{ $activeTab === 'notes' ? 'show active' : '' }}" id="notes">
        @can('cases.edit')
        <form action="{{ route('cases.notes.store', $case) }}" method="POST" class="mb-3">
            @csrf
            <textarea name="body" class="form-control form-control-sm mb-2" rows="2" placeholder="Add internal note..." required></textarea>
            <button type="submit" class="btn btn-primary btn-sm">Add note</button>
        </form>
        @endcan
        @forelse($case->notes as $note)
            <div class="border-start border-2 ps-2 mb-2">
                <small class="text-muted">{{ $note->user->name ?? '—' }} · {{ $note->created_at->format('Y-m-d H:i') }}</small>
                <p class="mb-0 small">{{ $note->body }}</p>
            </div>
        @empty
            <p class="text-muted small">No notes yet.</p>
        @endforelse
    </div>
    <div class="tab-pane fade {{ $activeTab === 'history' ? 'show active' : '' }}" id="history">
        <table class="table table-sm">
            <thead><tr><th>Date</th><th>User</th><th>Action</th></tr></thead>
            <tbody>
                @forelse($auditLogs as $log)
                    <tr>
                        <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                        <td>{{ $log->user?->name ?? $log->user_id ?? '—' }}</td>
                        <td>{{ $log->action }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-muted">No audit entries yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
