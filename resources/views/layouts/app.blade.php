<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - @yield('title', 'Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @stack('styles')
</head>
<body class="d-flex">
    {{-- Sidebar --}}
    <aside class="bg-dark text-white flex-shrink-0" id="sidebar" style="width: 260px; min-height: 100vh;">
        <div class="p-3 border-bottom border-secondary">
            <a href="{{ route('dashboard') }}" class="text-white text-decoration-none fw-bold">{{ config('app.name') }}</a>
        </div>
        <nav class="p-2">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link text-white {{ request()->routeIs('dashboard') ? 'bg-secondary bg-opacity-25' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                @php $registry = app(\App\Core\Support\ModuleRegistry::class); @endphp
                @foreach($registry->allMenuItems() as $group)
                    @if(count($group['children'] ?? []) > 0)
                        <li class="nav-item mt-2">
                            <span class="nav-link text-secondary small text-uppercase">{{ $group['label'] }}</span>
                            @foreach($group['children'] as $item)
                                @if(empty($item['permission']) || auth()->user()->can($item['permission']))
                                    <a class="nav-link text-white d-block py-1 ps-3 small {{ request()->routeIs($item['route']) ? 'bg-secondary bg-opacity-25' : '' }}" href="{{ route($item['route']) }}">{{ $item['label'] }}</a>
                                @endif
                            @endforeach
                        </li>
                    @endif
                @endforeach
                @if(auth()->user()->can('admin.users') || auth()->user()->can('admin.roles') || auth()->user()->can('admin.settings'))
                <li class="nav-item mt-2">
                    <span class="nav-link text-secondary small text-uppercase">Administration</span>
                    @can('admin.users')<a class="nav-link text-white d-block py-1 ps-3 small" href="{{ Route::has('admin.users.index') ? route('admin.users.index') : '#' }}">Users</a>@endcan
                    @can('admin.roles')<a class="nav-link text-white d-block py-1 ps-3 small" href="{{ Route::has('admin.roles.index') ? route('admin.roles.index') : '#' }}">Roles & Permissions</a>@endcan
                    @can('admin.settings')<a class="nav-link text-white d-block py-1 ps-3 small" href="{{ Route::has('admin.settings.index') ? route('admin.settings.index') : '#' }}">System Settings</a>@endcan
                </li>
                @endif
            </ul>
        </nav>
    </aside>

    <div class="flex-grow-1 d-flex flex-column min-vh-100">
        {{-- Top navbar --}}
        <header class="bg-white border-bottom shadow-sm">
            <div class="d-flex align-items-center justify-content-between px-3 py-2">
                <span class="text-muted small">@yield('breadcrumbs', 'Dashboard')</span>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small">{{ auth()->user()->name ?? auth()->user()->username }}</span>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">Profile</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @if(Route::has('profile.edit'))<li><a class="dropdown-item" href="{{ route('profile.edit') }}">Profile</a></li>@endif
                            @if(Route::has('password.request'))<li><a class="dropdown-item" href="{{ route('password.request') }}">Change password</a></li>@endif
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-item">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>

        <main class="p-4 flex-grow-1">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h1 class="h4 mb-0">@yield('page-title', 'Dashboard')</h1>
                @hasSection('actions')
                    <div>@yield('actions')</div>
                @endif
            </div>
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            @endif
            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
