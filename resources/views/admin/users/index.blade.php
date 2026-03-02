@extends('layouts.app')

@section('title', 'Users')
@section('page-title', 'Users')
@section('breadcrumbs', 'Administration / Users')

@section('actions')
    @can('admin.users')
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">Add user</a>
    @endcan
@endsection

@section('content')
<form method="GET" action="{{ route('admin.users.index') }}" class="mb-3">
    <div class="input-group" style="max-width: 320px;">
        <input type="text" name="search" class="form-control" placeholder="Search name, username, email" value="{{ request('search') }}">
        <button type="submit" class="btn btn-outline-secondary">Search</button>
    </div>
</form>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Roles</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->username }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->roles->pluck('name')->join(', ') ?: '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())<div class="card-footer bg-white">{{ $users->links() }}</div>@endif
</div>
@endsection
