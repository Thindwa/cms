@extends('layouts.app')

@section('title', 'Bulk Excel Import')
@section('breadcrumbs', 'Case Management / Excel Imports / Bulk')
@section('page-title', 'Bulk Excel Upload')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-body">
                <h6 class="mb-3">Bulk Workflow</h6>
                <ol class="small text-muted mb-4">
                    <li>Upload many files (same layout)</li>
                    <li>Review mapping once</li>
                    <li>Start queued import</li>
                    <li>Track progress bar and failures</li>
                </ol>

                <form method="POST" action="{{ route('cases.imports.bulk.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Batch Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', 'Legacy Excel Batch ' . now()->format('Y-m-d')) }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Files</label>
                        <input type="file" name="files[]" class="form-control @error('files') is-invalid @enderror" accept=".xlsx,.xls,.csv" multiple required>
                        @error('files')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Select all files together (up to 50MB per file).</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Sheet Name (Optional)</label>
                        <input type="text" name="sheet_name" class="form-control" value="{{ old('sheet_name', 'Sheet1') }}">
                    </div>

                    <button class="btn btn-primary">Create Bulk Batch</button>
                    <a href="{{ route('cases.imports.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
