@extends('layouts.app')

@section('title', 'Case ' . $case->case_number)
@section('page-title', 'Case ' . $case->case_number)
@section('breadcrumbs', 'Case Management / ' . $case->case_number)

@section('actions')
    @can('cases.edit')
        <a href="{{ route('cases.edit', $case) }}" class="btn btn-primary btn-sm">Edit</a>
    @endcan
@endsection

@push('styles')
<style>
.case-header-card { border: 0; border-radius: 12px; box-shadow: 0 2px 8px rgba(16, 24, 40, .08); }
.case-header-card .chip { font-size: .75rem; letter-spacing: .03em; text-transform: uppercase; padding: .35rem .6rem; border-radius: 999px; background: #eef4ff; color: #1f4db8; font-weight: 600; }
.overview-card { border: 0; border-radius: 12px; box-shadow: 0 1px 4px rgba(16, 24, 40, .08); }
.overview-list { margin: 0; padding: 0; list-style: none; }
.overview-list li { display: grid; grid-template-columns: 170px 1fr; gap: .75rem; padding: .55rem 0; border-bottom: 1px solid #f0f2f5; }
.overview-list li:last-child { border-bottom: 0; }
.overview-label { color: #667085; font-weight: 600; font-size: .9rem; }
.overview-value { color: #101828; }
.case-description { border: 1px solid #eaecf0; border-radius: 10px; background: #fcfcfd; padding: 1rem; max-height: 460px; overflow: auto; }
.case-description p:last-child { margin-bottom: 0; }
</style>
@endpush

@section('content')
<div class="card case-header-card mb-3">
    <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <div class="chip mb-2">Case Overview</div>
            <h4 class="mb-1">{{ $case->case_number }}</h4>
            <div class="text-muted small">Officer Dealing: {{ $case->title ?: '—' }}</div>
        </div>
        <div class="text-end small text-muted">
            <div>Created: {{ $case->created_at->format('Y-m-d H:i') }}</div>
            <div>Updated: {{ $case->updated_at->format('Y-m-d H:i') }}</div>
        </div>
    </div>
</div>

@php $activeTab = session('tab', 'overview'); @endphp
<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><a class="nav-link {{ $activeTab === 'overview' ? 'active' : '' }}" data-bs-toggle="tab" href="#overview">Overview</a></li>
    <li class="nav-item"><a class="nav-link {{ $activeTab === 'documents' ? 'active' : '' }}" data-bs-toggle="tab" href="#documents">Documents</a></li>
    <li class="nav-item"><a class="nav-link {{ $activeTab === 'notes' ? 'active' : '' }}" data-bs-toggle="tab" href="#notes">Officer Notes / Comments (Updates)</a></li>
    <li class="nav-item"><a class="nav-link {{ $activeTab === 'history' ? 'active' : '' }}" data-bs-toggle="tab" href="#history">History</a></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show {{ $activeTab === 'overview' ? 'active' : '' }}" id="overview">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card overview-card">
                    <div class="card-body">
                        <h6 class="mb-3">Case Details</h6>
                        <ul class="overview-list">
                            <li><span class="overview-label">Serial Number</span><span class="overview-value">{{ $case->case_number }}</span></li>
                            <li><span class="overview-label">Date Filed</span><span class="overview-value">{{ $case->date_filed?->format('Y-m-d') ?? '—' }}</span></li>
                            <li><span class="overview-label">Upcoming Hearing Date</span><span class="overview-value">{{ $case->hearing_date?->format('Y-m-d') ?? '—' }}</span></li>
                            <li><span class="overview-label">AG Reference Number</span><span class="overview-value">{{ $case->reference_number ?? '—' }}</span></li>
                            <li><span class="overview-label">Civil Case Number</span><span class="overview-value">{{ $case->civil_case_number ?? '—' }}</span></li>
                            <li><span class="overview-label">Cause Number</span><span class="overview-value">{{ $case->cause_number ?? '—' }}</span></li>
                            <li><span class="overview-label">Nature of Claim</span><span class="overview-value">{{ $case->nature_of_claim ?? '—' }}</span></li>
                        </ul>
                    </div>
                </div>
                <div class="card overview-card mt-3">
                    <div class="card-body">
                        <h6 class="mb-3">Case Summary (Primary)</h6>
                        @if($case->description)
                            <div class="case-description">{!! $case->description !!}</div>
                        @else
                            <p class="text-muted small mb-0">No case summary added.</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card overview-card mb-3">
                    <div class="card-body">
                        <h6 class="mb-3">Parties and Ownership</h6>
                        <ul class="overview-list">
                            <li><span class="overview-label">Officer Dealing</span><span class="overview-value">{{ $case->title ?: '—' }}</span></li>
                            <li><span class="overview-label">Entered By</span><span class="overview-value">{{ $case->createdByUser?->name ?? '—' }}</span></li>
                            <li><span class="overview-label">Claimant</span><span class="overview-value">{{ $case->claimant ?? '—' }}</span></li>
                            <li><span class="overview-label">Defendant</span><span class="overview-value">{{ $case->defendant ?? '—' }}</span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane fade {{ $activeTab === 'documents' ? 'show active' : '' }}" id="documents">
        @can('cases.edit')
        <form action="{{ route('cases.documents.store', $case) }}" method="POST" enctype="multipart/form-data" class="mb-3">
            @csrf
            <div class="mb-2">
                <input type="text" name="document_title" class="form-control form-control-sm" placeholder="Document title (optional)" value="{{ old('document_title') }}">
            </div>
            <div class="mb-2">
                <textarea name="document_details" class="form-control form-control-sm" rows="2" placeholder="Relevant details about this document (optional)">{{ old('document_details') }}</textarea>
            </div>
            <div class="input-group">
                <input type="file" name="documents[]" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif" multiple required>
                <button type="submit" class="btn btn-primary btn-sm">Upload</button>
            </div>
            <small class="text-muted">PDF, Word, Excel, images. You can select multiple files. Max 10MB each.</small>
        </form>
        @endcan
        <table class="table table-sm">
            <thead><tr><th>File name</th><th>Title</th><th>Details</th><th>Version</th><th>Type</th><th>Uploaded by</th><th>Date</th><th></th></tr></thead>
            <tbody>
                @forelse($case->documents->sortBy(['original_name', 'version']) as $doc)
                    <tr>
                        <td>{{ $doc->original_name }}</td>
                        <td>{{ $doc->title ?? '—' }}</td>
                        <td>{{ Str::limit($doc->details ?? '—', 80) }}</td>
                        <td>v{{ $doc->version }}</td>
                        <td>{{ $doc->display_type }}</td>
                        <td>{{ $doc->uploader?->name ?? '—' }}</td>
                        <td>{{ $doc->created_at->format('Y-m-d H:i') }}</td>
                        <td class="d-flex gap-1">
                            <a href="{{ route('cases.documents.download', [$case, $doc]) }}" class="btn btn-sm btn-outline-secondary">Download</a>
                            @can('cases.edit')
                            <form method="POST" action="{{ route('cases.documents.destroy', [$case, $doc->id]) }}"
                                  data-confirm-title="Delete Document"
                                  data-confirm-message="Move this document to recycle bin?"
                                  data-confirm-button="Move to Recycle Bin">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-muted">No documents yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-3">
            <a href="{{ route('cases.documents.recycle-bin') }}" class="btn btn-sm btn-outline-secondary">
                Open Recycle Bin
            </a>
        </div>
    </div>
    <div class="tab-pane fade {{ $activeTab === 'notes' ? 'show active' : '' }}" id="notes">
        @can('cases.edit')
        <form action="{{ route('cases.notes.store', $case) }}" method="POST" class="mb-3">
            @csrf
            <textarea name="body" class="form-control form-control-sm mb-2" rows="2" placeholder="Add officer note or comment update..." required></textarea>
            <button type="submit" class="btn btn-primary btn-sm">Add officer note</button>
        </form>
        @endcan
        @forelse($case->notes as $note)
            <div class="border-start border-2 ps-2 mb-2">
                <small class="text-muted">{{ $note->user->name ?? '—' }} · {{ $note->created_at->format('Y-m-d H:i') }}</small>
                <p class="mb-0 small">{{ $note->body }}</p>
            </div>
        @empty
            <p class="text-muted small">No officer notes/comments yet.</p>
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
