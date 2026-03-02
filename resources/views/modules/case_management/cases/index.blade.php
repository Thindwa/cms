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
                <label class="form-label small">Case title</label>
                <input type="text" name="title" class="form-control form-control-sm" value="{{ request('title') }}" placeholder="Title">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Officer Dealing</label>
                <select name="assigned_to" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($officers as $o)
                        <option value="{{ $o->id }}" {{ request('assigned_to') == $o->id ? 'selected' : '' }}>{{ $o->name }}</option>
                    @endforeach
                </select>
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
                    <th>{!! $sortLink('title', 'Title') !!}</th>
                    <th>{!! $sortLink('nature_of_claim', 'Nature of Claim') !!}</th>
                    <th>{!! $sortLink('status', 'Status') !!}</th>
                    <th>{!! $sortLink('assigned_to', 'Officer Dealing') !!}</th>
                    <th>{!! $sortLink('created_by', 'Entered By') !!}</th>
                    <th>{!! $sortLink('date_filed', 'Date Filed') !!}</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cases as $case)
                    <tr>
                        <td>{{ $case->case_number }}</td>
                        <td>{{ Str::limit($case->title, 40) }}</td>
                        <td>{{ $case->nature_of_claim ?? '—' }}</td>
                        <td>
                            @php
                                $badge = match($case->status) {
                                    'open' => 'bg-info',
                                    'in_progress' => 'bg-warning text-dark',
                                    'closed' => 'bg-secondary',
                                    default => 'bg-light text-dark'
                                };
                            @endphp
                            <span class="badge {{ $badge }}">{{ ucfirst(str_replace('_', ' ', $case->status)) }}</span>
                        </td>
                        <td>{{ $case->assignedOfficer?->name ?? '—' }}</td>
                        <td>{{ $case->createdByUser?->name ?? '—' }}</td>
                        <td>{{ $case->date_filed?->format('Y-m-d') ?? '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('cases.show', $case) }}" class="btn btn-sm btn-outline-primary">View</a>
                            @can('cases.edit')
                                <a href="{{ route('cases.edit', $case) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No cases found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($cases->hasPages())
        <div class="card-footer bg-white">{{ $cases->links() }}</div>
    @endif
</div>
@endsection
