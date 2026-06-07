@extends('layouts.app')

@section('title', 'Change Password')
@section('page-title', 'Change Password')
@section('breadcrumbs', 'Profile / Change Password')

@section('content')
<form action="{{ route('profile.password.update') }}" method="POST">
    @csrf
    @method('PUT')

    <div class="card border-0 shadow-sm" style="max-width: 640px;">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Current password</label>
                <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">New password</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-0">
                <label class="form-label">Confirm new password</label>
                <input type="password" name="password_confirmation" class="form-control" required>
            </div>
        </div>
        <div class="card-footer bg-white">
            <button type="submit" class="btn btn-primary">Update password</button>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</form>
@endsection
