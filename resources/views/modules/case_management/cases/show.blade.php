@extends('layouts.app')

@section('title', 'Case ' . $case->case_number)
@section('page-title', 'Case ' . $case->case_number)
@section('breadcrumbs', 'Case Management / ' . $case->case_number)

@section('actions')
    @can('update', $case)
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
@media (max-width: 575.98px) {
    .case-header-card .card-body { padding: 1rem; }
    .case-header-card .text-end { text-align: left !important; }
    .overview-list li { grid-template-columns: 1fr; gap: .15rem; }
    .case-description { padding: .875rem; }
}
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
            <div>Created: {{ $case->created_at->formatDateTime() }}</div>
            <div>Updated: {{ $case->updated_at->formatDateTime() }}</div>
        </div>
    </div>
</div>

@php $activeTab = session('tab', 'overview'); @endphp
<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><a class="nav-link {{ $activeTab === 'overview' ? 'active' : '' }}" data-bs-toggle="tab" href="#overview">Overview</a></li>
    <li class="nav-item"><a class="nav-link {{ $activeTab === 'documents' ? 'active' : '' }}" data-bs-toggle="tab" href="#documents">Documents</a></li>
    <li class="nav-item"><a class="nav-link {{ $activeTab === 'notes' ? 'active' : '' }}" data-bs-toggle="tab" href="#notes">Officer Notes / Comments (Updates)</a></li>
    <li class="nav-item"><a class="nav-link {{ $activeTab === 'activity' ? 'active' : '' }}" data-bs-toggle="tab" href="#activity">Activity Timeline</a></li>
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
                            <li><span class="overview-label">Case Title</span><span class="overview-value">{{ $case->case_title ?? '—' }}</span></li>
                            <li><span class="overview-label">Status</span><span class="overview-value">{{ $case->status ? ucfirst(str_replace('_', ' ', $case->status)) : '—' }}</span></li>
                            <li><span class="overview-label">Date Filed</span><span class="overview-value">{{ $case->date_filed?->formatDate() ?? '—' }}</span></li>
                            <li><span class="overview-label">Upcoming Hearing Date</span><span class="overview-value">{{ $case->hearing_date?->formatDate() ?? '—' }}</span></li>
                            <li><span class="overview-label">AG Reference Number</span><span class="overview-value">{{ $case->reference_number ?? '—' }}</span></li>
                            <li><span class="overview-label">Cause Number</span><span class="overview-value">{{ $case->cause_number ?? '—' }}</span></li>
                            <li><span class="overview-label">Category</span><span class="overview-value">{{ $case->category_name }}</span></li>
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
                            <li><span class="overview-label">Officer Dealing</span><span class="overview-value">
                                @can('update', $case)
                                <form method="POST" action="{{ route('cases.officer', $case) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <div class="input-group input-group-sm" style="max-width: 280px;">
                                        <input type="text" name="title" class="form-control form-control-sm" value="{{ $case->title }}" required>
                                        <button class="btn btn-outline-secondary btn-sm" type="submit"><i class="bi bi-check-lg"></i></button>
                                    </div>
                                </form>
                                @else
                                    {{ $case->title ?: '—' }}
                                @endcan
                            </span></li>
                            <li><span class="overview-label">Entered By</span><span class="overview-value">{{ $case->createdByUser?->name ?? '—' }}</span></li>
                            <li><span class="overview-label">Claimant</span><span class="overview-value">{{ $case->claimant ?? '—' }}</span></li>
                            <li><span class="overview-label">Defendant</span><span class="overview-value">{{ $case->defendant ?? '—' }}</span></li>
                        </ul>
                    </div>
                </div>
                <div class="card overview-card mb-3">
                    <div class="card-body">
                        <h6 class="mb-3">Officer Dealing History</h6>
                        @forelse($officerChanges as $log)
                            <div class="d-flex gap-2 mb-2 py-1 {{ !$loop->last ? 'border-bottom' : '' }}">
                                <div class="flex-shrink-0 mt-1">
                                    <i class="bi bi-arrow-counterclockwise text-primary fs-5"></i>
                                </div>
                                <div class="flex-grow-1 min-width-0">
                                    <div class="small fw-medium">
                                        {{ $log->actor?->name ?? 'System' }}
                                        <span class="fw-normal text-muted">· {{ $log->created_at->formatDateTime() }}</span>
                                    </div>
                                    <div class="small mt-1">
                                        <span class="text-decoration-line-through text-danger">{{ $log->old_values['title'] ?? '—' }}</span>
                                        <i class="bi bi-arrow-right mx-1 text-muted"></i>
                                        <span class="text-success">{{ $log->new_values['title'] ?? '—' }}</span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted small mb-0">No officer changes recorded.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane fade {{ $activeTab === 'documents' ? 'show active' : '' }}" id="documents">
        @can('uploadDocument', $case)
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
        <div class="table-responsive">
            <table class="table table-sm align-middle">
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
                            <td>{{ $doc->created_at->formatDateTime() }}</td>
                            <td class="d-flex gap-1 flex-wrap">
                                <a href="{{ route('cases.documents.download', [$case, $doc]) }}" class="btn btn-sm btn-outline-secondary">Download</a>
                                <button type="button" class="btn btn-sm btn-outline-primary preview-btn"
                                        data-url="{{ route('cases.documents.preview', [$case, $doc]) }}"
                                        data-name="{{ $doc->original_name }}"
                                        data-type="{{ $doc->mime_type }}">View</button>
                                @can('deleteDocument', $case)
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
        </div>
        @can('viewAny', \App\Modules\CaseManagement\Models\CaseDocument::class)
            <div class="mt-3">
                <a href="{{ route('cases.documents.recycle-bin') }}" class="btn btn-sm btn-outline-secondary">
                    Open Recycle Bin
                </a>
            </div>
        @endcan
    </div>
    <div class="tab-pane fade {{ $activeTab === 'notes' ? 'show active' : '' }}" id="notes">
        @can('createNote', $case)
        <form action="{{ route('cases.notes.store', $case) }}" method="POST" class="mb-3">
            @csrf
            <textarea id="notes-editor" name="body" class="form-control form-control-sm mb-2 @error('body') is-invalid @enderror" rows="3" placeholder="Add officer note or comment update...">{{ old('body') }}</textarea>
            @error('body')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            <button type="submit" class="btn btn-primary btn-sm">Add officer note</button>
        </form>
        @endcan
        @forelse($case->notes as $note)
            <div class="border-start border-2 ps-2 mb-2">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <small class="text-muted">{{ $note->user->name ?? '—' }} · {{ $note->created_at->formatDateTime() }}</small>
                    @if(auth()->user()?->can('updateNote', $case) || auth()->user()?->can('deleteNote', $case))
                        <div class="d-flex gap-1">
                            @can('updateNote', $case)
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#edit-note-{{ $note->id }}">
                                Edit
                            </button>
                            @endcan
                            @can('deleteNote', $case)
                            <form method="POST" action="{{ route('cases.notes.destroy', [$case, $note]) }}"
                                  data-confirm-title="Delete Note"
                                  data-confirm-message="Delete this note permanently?"
                                  data-confirm-button="Delete Note">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                            @endcan
                        </div>
                    @endif
                </div>
                <div class="mb-0 small">{!! $note->body !!}</div>
                @can('updateNote', $case)
                    <div class="collapse mt-2" id="edit-note-{{ $note->id }}">
                        <form method="POST" action="{{ route('cases.notes.update', [$case, $note]) }}">
                            @csrf
                            @method('PUT')
                            <textarea id="edit-note-editor-{{ $note->id }}" name="edit_body" class="form-control form-control-sm js-note-editor @error('edit_body') is-invalid @enderror" rows="3">{!! old('edit_body', $note->body) !!}</textarea>
                            @error('edit_body')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            <div class="d-flex gap-1 mt-2">
                                <button type="submit" class="btn btn-sm btn-primary">Save changes</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#edit-note-{{ $note->id }}">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                @endcan
            </div>
        @empty
            <p class="text-muted small">No officer notes/comments yet.</p>
        @endforelse
    </div>
    <div class="tab-pane fade {{ $activeTab === 'activity' ? 'show active' : '' }}" id="activity">
        @php $activities = $case->activities; @endphp
        @forelse($activities as $activity)
            <div class="d-flex gap-2 mb-2 py-1">
                <div class="flex-shrink-0 mt-1">
                    @php
                        $icon = match($activity->action) {
                            'case.created' => 'bi-plus-circle-fill text-success',
                            'case.updated' => 'bi-pencil-fill text-primary',
                            'case.deleted' => 'bi-trash-fill text-danger',
                            'case.categorized' => 'bi-tag-fill text-info',
                            'note.created' => 'bi-sticky-fill text-warning',
                            'note.updated' => 'bi-pencil-square text-primary',
                            'note.deleted' => 'bi-x-circle-fill text-danger',
                            'document.uploaded' => 'bi-file-earmark-arrow-up-fill text-success',
                            'document.deleted' => 'bi-file-earmark-x-fill text-danger',
                            'document.restored' => 'bi-arrow-counterclockwise text-info',
                            default => 'bi-record-circle text-secondary',
                        };
                    @endphp
                    <i class="bi {{ $icon }} fs-5"></i>
                </div>
                <div class="flex-grow-1 min-width-0">
                    <div class="small fw-medium">{{ $activity->description ?? Str::title(str_replace('.', ' ', $activity->action)) }}</div>
                    <div class="small text-muted">
                        {{ $activity->user?->name ?? 'System' }} · {{ $activity->created_at->diffForHumans() }}
                    </div>
                </div>
            </div>
        @empty
            <p class="text-muted small">No activity recorded yet.</p>
        @endforelse
    </div>
</div>
@push('styles')
<style>
#documentPreviewOffcanvas {
    --bs-offcanvas-width: 85vw;
}
#documentPreviewOffcanvas .offcanvas-body {
    display: flex;
    flex-direction: column;
    background: #e9ecef;
}
#documentPreviewOffcanvas .preview-toolbar {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding-bottom: .75rem;
}
#documentPreviewOffcanvas .preview-frame {
    flex: 1;
    border: 0;
    border-radius: 8px;
    background: #fff;
    min-height: 0;
}
#documentPreviewOffcanvas .preview-frame img {
    display: block;
    max-width: 100%;
    max-height: 100%;
    margin: auto;
    object-fit: contain;
}
#documentPreviewOffcanvas .preview-placeholder {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    color: #6c757d;
    text-align: center;
}
#documentPreviewOffcanvas .preview-placeholder i {
    font-size: 4rem;
}
@media (max-width: 767.98px) {
    #documentPreviewOffcanvas {
        --bs-offcanvas-width: 100vw;
    }
}
</style>
@endpush

{{-- Document Preview Offcanvas --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="documentPreviewOffcanvas" aria-labelledby="documentPreviewLabel">
    <div class="offcanvas-header bg-white border-bottom">
        <h5 class="offcanvas-title" id="documentPreviewLabel">Document Preview</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-3" id="previewBody">
        <div class="preview-toolbar">
            <span class="small text-muted text-truncate" id="previewFileName"></span>
            <a href="#" id="previewDownloadLink" class="btn btn-sm btn-outline-secondary ms-auto">Download</a>
        </div>
        <div class="preview-frame d-flex" id="previewFrame">
            <div class="preview-placeholder">
                <i class="bi bi-file-earmark"></i>
                <span>Select a document to preview</span>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const noteEditors = document.querySelectorAll('#notes-editor, .js-note-editor');
    if (!noteEditors.length) {
        return;
    }

    tinymce.init({
        selector: '#notes-editor, .js-note-editor',
        height: 180,
        menubar: false,
        branding: false,
        plugins: 'lists link code wordcount',
        toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link | removeformat | code',
        content_style: 'body { font-family: Segoe UI, Arial, sans-serif; font-size: 14px; }'
    });

    document.querySelectorAll('#notes form').forEach(function (form) {
        form.addEventListener('submit', function () {
            if (window.tinymce) {
                window.tinymce.triggerSave();
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const offcanvasEl = document.getElementById('documentPreviewOffcanvas');
    if (!offcanvasEl) return;

    const previewFrame = document.getElementById('previewFrame');
    const previewFileName = document.getElementById('previewFileName');
    const previewDownloadLink = document.getElementById('previewDownloadLink');

    let activeBsOffcanvas = null;

    document.querySelectorAll('.preview-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const url = this.dataset.url;
            const name = this.dataset.name;
            const mime = (this.dataset.type || '').toLowerCase();

            previewFileName.textContent = name;
            previewDownloadLink.href = url.replace('/preview', '/download');

            const isImage = mime.startsWith('image/');
            const isPdf = mime === 'application/pdf';

            if (isImage) {
                previewFrame.innerHTML = '<img src="' + url + '" alt="' + name + '" style="display:block;max-width:100%;max-height:100%;margin:auto;object-fit:contain;">';
            } else if (isPdf) {
                previewFrame.innerHTML = '<iframe src="' + url + '" style="flex:1;border:0;border-radius:8px;background:#fff;min-height:0;width:100%;" title="' + name + '"></iframe>';
            } else {
                previewFrame.innerHTML = '<div class="preview-placeholder"><i class="bi bi-file-earmark"></i><span>Preview not available for this file type.</span><a href="' + previewDownloadLink.href + '" class="btn btn-sm btn-primary">Download to view</a></div>';
            }

            if (!activeBsOffcanvas) {
                activeBsOffcanvas = new bootstrap.Offcanvas(offcanvasEl);
            }
            activeBsOffcanvas.show();
        });
    });
});
</script>
@endpush
