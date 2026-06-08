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
        html, body { overflow-x: hidden; }
        body { background: #f8f9fb; }
        .app-shell { min-height: 100vh; }
        .app-sidebar {
            width: 260px;
            min-height: 100vh;
        }
        .app-main {
            min-width: 0;
        }
        .app-topbar {
            min-height: 64px;
        }
        .sidebar-menu { padding: .5rem .5rem .75rem .5rem; }
        .sidebar-menu .nav-item { margin-bottom: .15rem; }
        .sidebar-menu .nav-item.mt-2 { margin-top: .85rem !important; }
        .sidebar-menu .nav-link { border-radius: .45rem; }
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
        .sidebar-menu .text-uppercase { padding-left: .75rem; letter-spacing: .04em; }
        .offcanvas.app-offcanvas {
            width: 290px;
            max-width: 88vw;
            background: #212529;
            color: #fff;
        }
        .offcanvas.app-offcanvas .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
        .app-content {
            padding: 1.5rem;
        }
        @media (max-width: 991.98px) {
            .app-content {
                padding: 1rem;
            }
            .app-topbar {
                min-height: auto;
            }
            .app-topbar .app-topbar-inner {
                gap: .75rem;
            }
            .app-topbar .app-topbar-meta {
                width: 100%;
                justify-content: space-between;
            }
        }
        @media (max-width: 575.98px) {
            .app-content {
                padding: .875rem;
            }
            .sidebar-link {
                font-size: .98rem;
            }
            .sidebar-link i {
                width: 1rem;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="app-shell d-flex flex-column flex-lg-row">
    {{-- Desktop Sidebar --}}
    <aside class="bg-dark text-white flex-shrink-0 app-sidebar d-none d-lg-flex flex-column position-sticky top-0" id="sidebar">
        <div class="p-3 border-bottom border-secondary">
            <a href="{{ route('dashboard') }}" class="text-white text-decoration-none fw-bold">{{ config('app.name') }}</a>
        </div>
        @include('layouts.partials.sidebar-menu')
    </aside>

    {{-- Mobile Sidebar --}}
    <div class="offcanvas offcanvas-start app-offcanvas d-lg-none" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
        <div class="offcanvas-header border-bottom border-secondary">
            <h5 class="offcanvas-title fw-bold" id="mobileSidebarLabel">{{ config('app.name') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            @include('layouts.partials.sidebar-menu')
        </div>
    </div>

    <div class="app-main flex-grow-1 d-flex flex-column min-vh-100">
        {{-- Top navbar --}}
        <header class="bg-white border-bottom shadow-sm app-topbar">
            <div class="d-flex align-items-center justify-content-between px-3 py-2 app-topbar-inner">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button class="btn btn-outline-secondary btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
                        <i class="bi bi-list"></i>
                    </button>
                    <span class="text-muted small text-break">@yield('breadcrumbs', 'Dashboard')</span>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap app-topbar-meta">
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

        <main class="app-content flex-grow-1">
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
