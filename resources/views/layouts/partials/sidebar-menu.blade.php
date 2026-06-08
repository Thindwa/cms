<nav class="p-2 sidebar-menu">
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
