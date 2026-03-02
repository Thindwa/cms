@extends('layouts.app')

@section('title', 'System Settings')
@section('page-title', 'System Settings')
@section('breadcrumbs', 'Administration / System Settings')

@section('content')
<form action="{{ route('admin.settings.update') }}" method="POST">
    @csrf
    @method('PUT')
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white">General</div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Application name</label>
                <input type="text" name="app_name" class="form-control @error('app_name') is-invalid @enderror" value="{{ old('app_name', $settings['app_name'] ?? '') }}" required>
                @error('app_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Date format</label>
                    <input type="text" name="date_format" class="form-control @error('date_format') is-invalid @enderror" value="{{ old('date_format', $settings['date_format'] ?? 'Y-m-d') }}" placeholder="e.g. Y-m-d">
                    @error('date_format')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Time format</label>
                    <input type="text" name="time_format" class="form-control @error('time_format') is-invalid @enderror" value="{{ old('time_format', $settings['time_format'] ?? 'H:i') }}" placeholder="e.g. H:i">
                    @error('time_format')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Items per page (lists)</label>
                <input type="number" name="items_per_page" class="form-control @error('items_per_page') is-invalid @enderror" value="{{ old('items_per_page', $settings['items_per_page'] ?? 15) }}" min="5" max="100">
                @error('items_per_page')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="card-footer bg-white">
            <button type="submit" class="btn btn-primary">Save settings</button>
        </div>
    </div>
</form>
@endsection
