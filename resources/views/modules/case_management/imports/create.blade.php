@extends('layouts.app')

@section('title', 'New Excel Import')
@section('breadcrumbs', 'Case Management / Excel Imports / New')
@section('page-title', 'Upload Excel File')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h6 class="mb-3">Import Workflow</h6>
                <ol class="small text-muted mb-4">
                    <li>Upload file</li>
                    <li>Review auto-mapping and resolve mismatches</li>
                    <li>Run dry-run</li>
                    <li>Execute final import</li>
                </ol>

                <form method="POST" action="{{ route('cases.imports.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Excel File</label>
                        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx,.xls,.csv" required>
                        @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Supported: .xlsx, .xls, .csv (max 50MB).</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Sheet Name (Optional)</label>
                        <input type="text" name="sheet_name" class="form-control @error('sheet_name') is-invalid @enderror" value="{{ old('sheet_name', 'Sheet1') }}" placeholder="Sheet1">
                        @error('sheet_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">If empty or invalid, first sheet is used.</div>
                    </div>

                    <button class="btn btn-primary">Upload and Analyze</button>
                    <a href="{{ route('cases.imports.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
