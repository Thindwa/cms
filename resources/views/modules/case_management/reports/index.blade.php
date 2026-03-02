@extends('layouts.app')

@section('title', 'Reports')
@section('page-title', 'Reports')
@section('breadcrumbs', 'Case Management / Reports')

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('cases.reports') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Report type</label>
                <select name="report_type" class="form-select form-select-sm">
                    <option value="summary" {{ ($reportType ?? '') === 'summary' ? 'selected' : '' }}>Cases summary</option>
                    <option value="by_officer" {{ ($reportType ?? '') === 'by_officer' ? 'selected' : '' }}>Cases per officer</option>
                    <option value="by_status" {{ ($reportType ?? '') === 'by_status' ? 'selected' : '' }}>Cases by status</option>
                    <option value="by_category" {{ ($reportType ?? '') === 'by_category' ? 'selected' : '' }}>Cases by nature of claim</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo ?? '' }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm">Generate</button>
            </div>
        </form>
    </div>
</div>

@if(isset($data))
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span>{{ $data['title'] }}</span>
        <div class="btn-group btn-group-sm">
            @can('reports.export')
            <a href="{{ route('cases.reports', array_merge(request()->query(), ['export' => 'pdf'])) }}" class="btn btn-outline-secondary">Export PDF</a>
            <a href="{{ route('cases.reports', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-outline-secondary">Export Excel</a>
            @endcan
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">Print</button>
        </div>
    </div>
    <div class="card-body report-body">
        <p class="text-muted small">Period: {{ $dateFrom }} to {{ $dateTo }}</p>
        <table class="table table-sm table-bordered">
            @if(!empty($data['rows']))
            <thead class="table-light">
                <tr>
                    @foreach(array_keys($data['rows'][0] ?? []) as $th)
                        <th>{{ $th }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($data['rows'] as $row)
                    <tr>
                        @foreach($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
            @else
            <tbody><tr><td class="text-muted">No data for this period.</td></tr></tbody>
            @endif
        </table>
    </div>
</div>
@endif
@endsection
