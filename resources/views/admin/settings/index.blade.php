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
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white">SMTP (Email)</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Mailer</label>
                    <input type="text" name="mail_mailer" class="form-control @error('mail_mailer') is-invalid @enderror" value="{{ old('mail_mailer', $settings['mail_mailer'] ?? 'smtp') }}" placeholder="smtp">
                    @error('mail_mailer')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-5 mb-3">
                    <label class="form-label">SMTP Host</label>
                    <input type="text" name="smtp_host" class="form-control @error('smtp_host') is-invalid @enderror" value="{{ old('smtp_host', $settings['smtp_host'] ?? '') }}" placeholder="smtp.example.com">
                    @error('smtp_host')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">SMTP Port</label>
                    <input type="number" name="smtp_port" class="form-control @error('smtp_port') is-invalid @enderror" value="{{ old('smtp_port', $settings['smtp_port'] ?? 587) }}" min="1" max="65535">
                    @error('smtp_port')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Encryption</label>
                    <input type="text" name="smtp_encryption" class="form-control @error('smtp_encryption') is-invalid @enderror" value="{{ old('smtp_encryption', $settings['smtp_encryption'] ?? 'tls') }}" placeholder="tls/ssl">
                    @error('smtp_encryption')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">SMTP Username</label>
                    <input type="text" name="smtp_username" class="form-control @error('smtp_username') is-invalid @enderror" value="{{ old('smtp_username', $settings['smtp_username'] ?? '') }}">
                    @error('smtp_username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">SMTP Password</label>
                    <input type="password" name="smtp_password" class="form-control @error('smtp_password') is-invalid @enderror" value="{{ old('smtp_password', $settings['smtp_password'] ?? '') }}">
                    @error('smtp_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">From Name</label>
                    <input type="text" name="smtp_from_name" class="form-control @error('smtp_from_name') is-invalid @enderror" value="{{ old('smtp_from_name', $settings['smtp_from_name'] ?? config('app.name')) }}">
                    @error('smtp_from_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">From Address</label>
                    <input type="email" name="smtp_from_address" class="form-control @error('smtp_from_address') is-invalid @enderror" value="{{ old('smtp_from_address', $settings['smtp_from_address'] ?? '') }}" placeholder="noreply@example.com">
                    @error('smtp_from_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white">Upcoming Hearing Notifications</div>
        <div class="card-body">
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="upcoming_notifications_enabled" name="upcoming_notifications_enabled" value="1" @checked((int) old('upcoming_notifications_enabled', $settings['upcoming_notifications_enabled'] ?? 0) === 1)>
                <label class="form-check-label" for="upcoming_notifications_enabled">Enable upcoming hearing reminders</label>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Notify days before hearing</label>
                    <input type="number" name="upcoming_notifications_days" class="form-control @error('upcoming_notifications_days') is-invalid @enderror" min="1" max="60" value="{{ old('upcoming_notifications_days', $settings['upcoming_notifications_days'] ?? 7) }}">
                    @error('upcoming_notifications_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Notification time (daily)</label>
                    <input type="time" name="upcoming_notifications_time" class="form-control @error('upcoming_notifications_time') is-invalid @enderror" value="{{ old('upcoming_notifications_time', $settings['upcoming_notifications_time'] ?? '08:00') }}">
                    @error('upcoming_notifications_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <small class="text-muted">Reminders are sent to users with case access and email addresses configured.</small>
        </div>
        <div class="card-footer bg-white">
            <button type="submit" class="btn btn-primary">Save settings</button>
        </div>
    </div>
</form>
@endsection
