@extends('layouts.guest')

@section('title', 'Login')

@section('content')
<div class="card shadow-sm" style="max-width: 420px; width: 100%;">
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <h4 class="fw-bold text-secondary">{{ config('app.name') }}</h4>
            <p class="text-muted small">Case Management System</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger py-2">
                @foreach ($errors->all() as $error)
                    <small>{{ $error }}</small>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" id="login-form">
            @csrf
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control @error('username') is-invalid @enderror" id="username"
                    name="username" value="{{ old('username') }}" required autofocus autocomplete="username">
                @error('username')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                    name="password" required autocomplete="current-password">
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label" for="remember">Remember me</label>
            </div>
            <button type="submit" class="btn btn-primary w-100" id="btn-login">Login</button>
        </form>

        @if(Route::has('password.request'))
        <div class="text-center mt-3">
            <a href="{{ route('password.request') }}" class="small text-decoration-none">Forgot password?</a>
        </div>
        @endif
    </div>
</div>

<script>
document.getElementById('login-form').addEventListener('submit', function () {
    document.getElementById('btn-login').disabled = true;
    document.getElementById('btn-login').textContent = 'Signing in...';
});
</script>
@endsection
