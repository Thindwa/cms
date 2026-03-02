@extends('layouts.app')

@section('title', 'Edit role')
@section('page-title', 'Edit role: ' . $role->name)
@section('breadcrumbs', 'Administration / Roles / Edit')

@section('content')
<form action="{{ route('admin.roles.update', $role) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            @foreach($permissions as $group => $perms)
                <div class="mb-4">
                    <h6 class="text-muted text-uppercase small">{{ $group }}</h6>
                    <div class="row">
                        @foreach($perms as $permission)
                            <div class="col-md-4 col-lg-3">
                                <div class="form-check">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" class="form-check-input" id="perm-{{ $permission->id }}" {{ $role->hasPermissionTo($permission->name) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="perm-{{ $permission->id }}">{{ $permission->name }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        <div class="card-footer bg-white">
            <button type="submit" class="btn btn-primary">Update permissions</button>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</form>
@endsection
