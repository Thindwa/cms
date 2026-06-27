@extends('layouts.app')

@section('title', 'Recycle Bin')
@section('page-title', 'Document Recycle Bin')
@section('breadcrumbs', 'Case Management / Recycle Bin')

@section('content')
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('cases.documents.recycle-bin') }}" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label small">Search by file or case</label>
                <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="File name, case number, or officer">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary btn-sm">Search</button>
                <a href="{{ route('cases.documents.recycle-bin') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>File name</th>
                        <th>Case</th>
                        <th>Officer Dealing</th>
                        <th>Deleted by</th>
                        <th>Deleted at</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $doc)
                        <tr>
                            <td>{{ $doc->original_name }}</td>
                            <td>{{ $doc->case?->case_number ?? '—' }}</td>
                            <td>{{ $doc->case?->title ?? '—' }}</td>
                            <td>{{ $doc->deletedByUser?->name ?? '—' }}</td>
                            <td>{{ $doc->deleted_at?->formatDateTime() ?? '—' }}</td>
                            <td class="text-end d-flex justify-content-end gap-1">
                                @if($doc->case)
                                @can('view', $doc->case)
                                    <a href="{{ route('cases.show', $doc->case_id) }}" class="btn btn-sm btn-outline-secondary">Open Case</a>
                                @endcan
                                @endif
                                @if($doc->case)
                                @can('restoreDocument', $doc->case)
                                <form method="POST" action="{{ route('cases.documents.restore', [$doc->case_id, $doc->id]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success">Restore</button>
                                </form>
                                @endcan
                                @endif
                                @can('purge', $doc)
                                    <form method="POST" action="{{ route('cases.documents.purge', $doc->id) }}"
                                          data-confirm-title="Delete Permanently"
                                          data-confirm-message="Delete this file permanently? This cannot be undone."
                                          data-confirm-button="Delete Permanently">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete Permanently</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted text-center py-4">Recycle bin is empty.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($documents->hasPages())
        <div class="card-footer bg-white">{{ $documents->links() }}</div>
    @endif
</div>
@endsection
