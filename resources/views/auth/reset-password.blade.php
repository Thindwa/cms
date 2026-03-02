@extends('layouts.guest')

@section('title', 'Reset Password')

@section('content')
<div class="card shadow-sm" style="max-width: 420px; width: 100%;">
    <div class="card-body p-4">
        <h5 class="card-title mb-3">Reset password</h5>
        @if ($errors->any())
            <div class="alert alert-danger py-2">
                @foreach ($errors->all() as $error)<small>{{ $error }}</small>@endforeach
            </div>
        @endif
        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $email) }}" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">New password</label>
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Confirm password</label>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Reset password</button>
        </form>
    </div>
</div>
@endsection
