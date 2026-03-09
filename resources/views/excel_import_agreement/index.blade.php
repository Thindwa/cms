@extends('layouts.app')

@section('title', 'Import Agreement Responses')
@section('page-title', 'Import Agreement Responses')
@section('breadcrumbs', 'Excel Import Agreement / Responses')

@section('actions')
    <a href="{{ route('excel-import-agreement.create') }}" class="btn btn-primary btn-sm">New Response</a>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Submitted By</th>
                    <th>Source File</th>
                    <th>Source Sheet</th>
                    <th>Created</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($responses as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td>{{ $item->submitter?->name ?? $item->submitter?->username ?? '—' }}</td>
                        <td>{{ $item->source_file ?? '—' }}</td>
                        <td>{{ $item->source_sheet ?? '—' }}</td>
                        <td>{{ $item->created_at->format('Y-m-d H:i') }}</td>
                        <td class="text-end">
                            <a href="{{ route('excel-import-agreement.show', $item) }}" class="btn btn-sm btn-outline-primary">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No responses yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($responses->hasPages())
        <div class="card-footer bg-white">{{ $responses->links() }}</div>
    @endif
</div>
@endsection
