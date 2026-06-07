@extends('layouts.app')

@section('title', 'Case Management')
@section('page-title', 'Case Management')
@section('breadcrumbs', 'Case Management / Case List')

@section('actions')
    @can('cases.create')
        <a href="{{ route('cases.create') }}" class="btn btn-primary btn-sm">Register New Case</a>
    @endcan
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('cases.index') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small">Case No</label>
                <input type="text" name="case_number" class="form-control form-control-sm" value="{{ request('case_number') }}" placeholder="Serial number">
            </div>
            <div class="col-md-2">
                <label class="form-label small">AG Ref No</label>
                <input type="text" name="reference_number" class="form-control form-control-sm" value="{{ request('reference_number') }}" placeholder="AG/...">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Case Title</label>
                <input type="text" name="case_title" class="form-control form-control-sm" value="{{ request('case_title') }}" placeholder="Case title">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Civil Case No</label>
                <input type="text" name="civil_case_number" class="form-control form-control-sm" value="{{ request('civil_case_number') }}" placeholder="Civil case no">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Officer Dealing</label>
                <input type="text" name="title" class="form-control form-control-sm" value="{{ request('title') }}" placeholder="Officer dealing">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Party</label>
                <input type="text" name="party" class="form-control form-control-sm" value="{{ request('party') }}" placeholder="Claimant/Defendant">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Date from</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Date to</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Hearing from</label>
                <input type="date" name="hearing_date_from" class="form-control form-control-sm" value="{{ request('hearing_date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Hearing to</label>
                <input type="date" name="hearing_date_to" class="form-control form-control-sm" value="{{ request('hearing_date_to') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm me-1">Search</button>
                <a href="{{ route('cases.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    @php
                        $queryParams = request()->except(['sort_by', 'sort_dir']);
                        $sortLink = function ($col, $label) use ($queryParams) {
                            $dir = (request('sort_by') === $col && request('sort_dir') === 'asc') ? 'desc' : 'asc';
                            $url = route('cases.index', array_merge($queryParams, ['sort_by' => $col, 'sort_dir' => $dir]));
                            $arrow = request('sort_by') === $col ? (request('sort_dir') === 'asc' ? ' ↑' : ' ↓') : '';
                            return '<a href="' . e($url) . '" class="text-decoration-none text-dark">' . e($label) . $arrow . '</a>';
                        };
                    @endphp
                    <th>{!! $sortLink('case_number', 'Serial No') !!}</th>
                    <th>{!! $sortLink('reference_number', 'AG Ref No') !!}</th>
                    <th>{!! $sortLink('title', 'Officer Dealing') !!}</th>
                    <th>{!! $sortLink('status', 'Status') !!}</th>
                    <th>{!! $sortLink('claimant', 'Claimant / Party') !!}</th>
                    <th>{!! $sortLink('date_filed', 'Date Filed') !!}</th>
                    <th>{!! $sortLink('hearing_date', 'Hearing Date') !!}</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cases as $case)
                    <tr>
                        <td>{{ $case->case_number }}</td>
                        <td>{{ $case->reference_number ?? '—' }}</td>
                        <td>{{ Str::limit($case->title, 28) ?: '—' }}</td>
                        <td>
                            @if($case->status)
                                <span class="badge text-bg-light border">{{ ucfirst(str_replace('_', ' ', $case->status)) }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ Str::limit($case->claimant ?: ($case->defendant ?? '—'), 40) }}</td>
                        <td>{{ $case->date_filed?->format('Y-m-d') ?? '—' }}</td>
                        <td>{{ $case->hearing_date?->format('Y-m-d') ?? '—' }}</td>
                        <td class="text-end">
                            @can('view', $case)
                                <a href="{{ route('cases.show', $case) }}" class="btn btn-sm btn-outline-primary">View</a>
                            @endcan
                            @can('update', $case)
                                <a href="{{ route('cases.edit', $case) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            @endcan
                            @can('delete', $case)
                                <form method="POST" action="{{ route('cases.destroy', $case) }}" class="d-inline"
                                      data-confirm-title="Delete Case"
                                      data-confirm-message="Delete {{ $case->case_number }}? This removes it from case list."
                                      data-confirm-button="Delete Case">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No cases found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
            <small class="text-muted">
                Showing {{ $cases->firstItem() ?? 0 }} to {{ $cases->lastItem() ?? 0 }} of {{ $cases->total() }} results
            </small>
            @if($cases->hasPages())
                <div>{{ $cases->onEachSide(1)->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
