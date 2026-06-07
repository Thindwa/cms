@extends('layouts.app')

@section('title', 'Add role')
@section('page-title', 'Add role')
@section('breadcrumbs', 'Administration / Roles / Add')

@section('content')
<form action="{{ route('admin.roles.store') }}" method="POST">
    @csrf
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Role name</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="card-footer bg-white">
            @can('admin.roles.create')
                <button type="submit" class="btn btn-primary">Create role</button>
            @endcan
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</form>
@endsection
