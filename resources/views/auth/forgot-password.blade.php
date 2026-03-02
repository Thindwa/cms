@extends('layouts.guest')

@section('title', 'Forgot Password')

@section('content')
<div class="card shadow-sm" style="max-width: 420px; width: 100%;">
    <div class="card-body p-4">
        <h5 class="card-title mb-3">Forgot password</h5>
        <p class="text-muted small">Enter your email and we'll send you a link to reset your password.</p>
        @if (session('status'))
            <div class="alert alert-success small">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger py-2">
                @foreach ($errors->all() as $error)<small>{{ $error }}</small>@endforeach
            </div>
        @endif
        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autofocus>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-primary w-100">Send reset link</button>
        </form>
        <div class="text-center mt-3">
            <a href="{{ route('login') }}" class="small text-decoration-none">Back to login</a>
        </div>
    </div>
</div>
@endsection
