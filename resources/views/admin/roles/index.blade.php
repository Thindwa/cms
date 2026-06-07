@extends('layouts.app')

@section('title', 'Roles & Permissions')
@section('page-title', 'Roles & Permissions')
@section('breadcrumbs', 'Administration / Roles')

@section('actions')
    @can('admin.roles.create')
        <a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm">Add role</a>
    @endcan
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Role</th>
                    <th>Permissions</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($roles as $role)
                    <tr>
                        <td>{{ $role->name }}</td>
                        <td>{{ $role->permissions_count }}</td>
                        <td class="text-end">
                            @can('admin.roles.edit')
                                <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-outline-primary">Edit permissions</a>
                            @endcan
                            @can('admin.roles.delete')
                                @if($role->name !== 'Super Admin')
                                    <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="d-inline"
                                          data-confirm-title="Delete Role"
                                          data-confirm-message="Delete role {{ $role->name }}?"
                                          data-confirm-button="Delete Role">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
