@extends('layouts.app')

@section('title', 'Agreement Response #' . $response->id)
@section('page-title', 'Agreement Response #' . $response->id)
@section('breadcrumbs', 'Excel Import Agreement / Response ' . $response->id)

@section('actions')
    <a href="{{ route('excel-import-agreement.index') }}" class="btn btn-outline-secondary btn-sm">Back to Responses</a>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4"><strong>Submitted By:</strong> {{ $response->submitter?->name ?? $response->submitter?->username ?? '—' }}</div>
            <div class="col-md-4"><strong>Submitted At:</strong> {{ $response->created_at->format('Y-m-d H:i') }}</div>
            <div class="col-md-4"><strong>Source:</strong> {{ ($response->source_file ?? '—') . ' / ' . ($response->source_sheet ?? '—') }}</div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h6>Key Answers</h6>
        <table class="table table-sm">
            <tr><th style="width:35%">Row Represents</th><td>{{ $response->row_represents }} {{ $response->row_represents_other ? '('.$response->row_represents_other.')' : '' }}</td></tr>
            <tr><th>Officer Dealing Source</th><td>{{ $response->officer_dealing_source }} {{ $response->officer_dealing_other ? '('.$response->officer_dealing_other.')' : '' }}</td></tr>
            <tr><th>Duplicate Handling</th><td>{{ $response->duplicate_handling }} {{ $response->duplicate_handling_other ? '('.$response->duplicate_handling_other.')' : '' }}</td></tr>
            <tr><th>Missing Required Policy</th><td>{{ $response->missing_required_policy }} {{ $response->missing_required_default ? '(Default: '.$response->missing_required_default.')' : '' }}</td></tr>
            <tr><th>Date Parsing</th><td>{{ implode(', ', $response->date_parsing ?? []) }} {{ $response->date_parsing_other ? '('.$response->date_parsing_other.')' : '' }}</td></tr>
            <tr><th>Text Handling</th><td>{{ $response->text_handling }} {{ $response->text_handling_other ? '('.$response->text_handling_other.')' : '' }}</td></tr>
            <tr><th>Entered By Mapping</th><td>{{ $response->entered_by_mapping }} {{ $response->entered_by_fallback_user ? '(Fallback: '.$response->entered_by_fallback_user.')' : '' }}</td></tr>
            <tr><th>Import Scope</th><td>{{ implode(', ', $response->import_scope ?? []) }} {{ $response->import_scope_other ? '('.$response->import_scope_other.')' : '' }}</td></tr>
            <tr><th>Audit & Rollback</th><td>{{ $response->audit_and_rollback ?? '—' }}</td></tr>
            <tr><th>Cutover Window</th><td>{{ $response->cutover_window ?? '—' }}</td></tr>
        </table>

        <h6>Field Mapping</h6>
        <table class="table table-sm table-bordered">
            <thead><tr><th>Excel Column</th><th>CMS Mapping</th><th>Confirmed</th><th>Note</th></tr></thead>
            <tbody>
                @foreach(($response->field_mapping ?? []) as $excel => $mapped)
                    @if(is_array($mapped))
                        <tr>
                            <td>{{ $excel }}</td>
                            <td>{{ $mapped['target'] ?? '—' }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $mapped['confirmed'] ?? 'needs_review')) }}</td>
                            <td>{{ $mapped['note'] ?? '—' }}</td>
                        </tr>
                    @else
                        <tr><td>{{ $excel }}</td><td>{{ $mapped ?: '—' }}</td><td>—</td><td>—</td></tr>
                    @endif
                @endforeach
            </tbody>
        </table>

    </div>
</div>
@endsection
