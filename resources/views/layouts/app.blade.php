<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - @yield('title', 'Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        #sidebar nav { padding: .5rem .5rem .75rem .5rem; }
        #sidebar .nav-item { margin-bottom: .15rem; }
        #sidebar .nav-item.mt-2 { margin-top: .85rem !important; }
        #sidebar .nav-link { border-radius: .45rem; }
        .sidebar-link {
            display: flex !important;
            align-items: center;
            gap: .6rem;
            padding: .45rem .75rem !important;
            line-height: 1.2;
        }
        .sidebar-link i {
            width: 1.1rem;
            text-align: center;
            opacity: .9;
        }
        #sidebar .text-uppercase { padding-left: .75rem; letter-spacing: .04em; }
    </style>
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
                    <a class="nav-link text-white sidebar-link {{ request()->routeIs('dashboard') ? 'bg-secondary bg-opacity-25' : '' }}" href="{{ route('dashboard') }}">
                        <i class="bi bi-grid-1x2"></i><span>Dashboard</span>
                    </a>
                </li>
                @php $registry = app(\App\Core\Support\ModuleRegistry::class); @endphp
                @foreach($registry->allMenuItems() as $group)
                    @if(count($group['children'] ?? []) > 0)
                        <li class="nav-item mt-2">
                            <span class="nav-link text-secondary small text-uppercase">{{ $group['label'] }}</span>
                            @foreach($group['children'] as $item)
                                @if(empty($item['permission']) || auth()->user()->can($item['permission']))
                                    @php
                                        $routePattern = str_ends_with($item['route'], '.index') ? str_replace('.index', '.*', $item['route']) : null;
                                        $active = request()->routeIs($item['route']) || ($routePattern && request()->routeIs($routePattern));
                                    @endphp
                                    <a class="nav-link text-white d-block small sidebar-link {{ $active ? 'bg-secondary bg-opacity-25' : '' }}" href="{{ route($item['route']) }}">
                                        <i class="bi {{ $item['icon'] ?? 'bi-dot' }}"></i><span>{{ $item['label'] }}</span>
                                    </a>
                                @endif
                            @endforeach
                        </li>
                    @endif
                @endforeach
                @if(auth()->user()->can('admin.users.view') || auth()->user()->can('admin.roles.view') || auth()->user()->can('admin.settings.view') || auth()->user()->can('admin.audit.view'))
                <li class="nav-item mt-2">
                    <span class="nav-link text-secondary small text-uppercase">Administration</span>
                    @can('admin.users.view')
                        <a class="nav-link text-white d-block small sidebar-link {{ request()->routeIs('admin.users.*') ? 'bg-secondary bg-opacity-25' : '' }}" href="{{ Route::has('admin.users.index') ? route('admin.users.index') : '#' }}">
                            <i class="bi bi-people"></i><span>Users</span>
                        </a>
                    @endcan
                    @can('admin.roles.view')
                        <a class="nav-link text-white d-block small sidebar-link {{ request()->routeIs('admin.roles.*') ? 'bg-secondary bg-opacity-25' : '' }}" href="{{ Route::has('admin.roles.index') ? route('admin.roles.index') : '#' }}">
                            <i class="bi bi-shield-lock"></i><span>Roles & Permissions</span>
                        </a>
                    @endcan
                    @can('admin.settings.view')
                        <a class="nav-link text-white d-block small sidebar-link {{ request()->routeIs('admin.settings.*') ? 'bg-secondary bg-opacity-25' : '' }}" href="{{ Route::has('admin.settings.index') ? route('admin.settings.index') : '#' }}">
                            <i class="bi bi-gear"></i><span>System Settings</span>
                        </a>
                    @endcan
                    @can('admin.audit.view')
                        <a class="nav-link text-white d-block small sidebar-link {{ request()->routeIs('admin.audit.*') ? 'bg-secondary bg-opacity-25' : '' }}" href="{{ Route::has('admin.audit.index') ? route('admin.audit.index') : '#' }}">
                            <i class="bi bi-journal-text"></i><span>Audit Logs</span>
                        </a>
                    @endcan
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
                            @if(Route::has('profile.password.edit'))<li><a class="dropdown-item" href="{{ route('profile.password.edit') }}">Change password</a></li>@endif
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

    <div class="modal fade" id="confirmActionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmActionTitle">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="confirmActionMessage">
                    Are you sure you want to continue?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmActionSubmit">Continue</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const modalElement = document.getElementById('confirmActionModal');
            if (!modalElement || typeof bootstrap === 'undefined') {
                return;
            }

            const modal = new bootstrap.Modal(modalElement);
            const titleEl = document.getElementById('confirmActionTitle');
            const messageEl = document.getElementById('confirmActionMessage');
            const submitEl = document.getElementById('confirmActionSubmit');
            let pendingForm = null;

            document.addEventListener('submit', function (event) {
                const form = event.target;
                if (!(form instanceof HTMLFormElement)) {
                    return;
                }

                if (form.dataset.confirmed === '1') {
                    form.dataset.confirmed = '0';
                    return;
                }

                if (!form.dataset.confirmMessage) {
                    return;
                }

                event.preventDefault();
                pendingForm = form;
                titleEl.textContent = form.dataset.confirmTitle || 'Confirm Action';
                messageEl.textContent = form.dataset.confirmMessage || 'Are you sure you want to continue?';
                submitEl.textContent = form.dataset.confirmButton || 'Continue';
                modal.show();
            }, true);

            submitEl.addEventListener('click', function () {
                if (!pendingForm) {
                    return;
                }

                pendingForm.dataset.confirmed = '1';
                if (typeof pendingForm.requestSubmit === 'function') {
                    pendingForm.requestSubmit();
                } else {
                    pendingForm.submit();
                }
                pendingForm = null;
                modal.hide();
            });

            modalElement.addEventListener('hidden.bs.modal', function () {
                pendingForm = null;
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
