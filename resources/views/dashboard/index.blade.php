@extends('layouts.app')

@section('title', 'Dashboard')
@section('breadcrumbs', 'Dashboard')

@push('styles')
<style>
.dashboard-card {
    border: none;
    border-radius: 12px;
    transition: transform .15s ease, box-shadow .15s ease;
}
.dashboard-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,.08) !important;
}
.dashboard-card .card-body { padding: 1.25rem 1.5rem; }
.dashboard-card .stat-value { font-size: 1.75rem; font-weight: 700; letter-spacing: -0.02em; }
.dashboard-card .stat-label { font-size: 0.8rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.04em; opacity: .85; }
/* Total Cases - Bootstrap Primary */
.kpi-total {
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
    color: #fff;
}

/* Active Cases - Bootstrap Success */
.kpi-active {
    background: linear-gradient(135deg, #198754 0%, #157347 100%);
    color: #fff;
}

/* Dormant Cases - Bootstrap Secondary */
.kpi-dormant {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    color: #fff;
}

/* Closed Cases - Bootstrap Danger */
.kpi-closed {
    background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%);
    color: #fff;
}
.chart-card { border: none; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
.chart-card .card-header { border-bottom: 1px solid rgba(0,0,0,.06); font-weight: 600; padding: 1rem 1.25rem; background: #fff; border-radius: 12px 12px 0 0; }
.activity-item { padding: .75rem 0; border-bottom: 1px solid rgba(0,0,0,.06); display: flex; align-items: flex-start; gap: .75rem; }
.activity-item:last-child { border-bottom: 0; }
.activity-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; margin-top: 5px; background: #0d6efd; }
.activity-meta { font-size: 0.8rem; color: #6c757d; }
.activity-action { font-weight: 500; color: #212529; }
.chart-container { position: relative; height: 280px; }
.quick-action-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: .75rem; }
.status-badge { font-size: 0.75rem; padding: 0.2em 0.5em; border-radius: 10px; font-weight: 500; }
@media (max-width: 991.98px) {
    .dashboard-card .card-body { padding: 1rem 1.1rem; }
    .dashboard-card .stat-value { font-size: 1.5rem; }
    .chart-container { height: 240px; }
}
@media (max-width: 575.98px) {
    .dashboard-card .card-body { padding: .9rem 1rem; }
    .chart-card .card-header { padding: .85rem 1rem; }
    .chart-container { height: 220px; }
    .quick-action-grid { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card dashboard-card shadow-sm kpi-total">
            <div class="card-body">
                <div class="stat-label">Total Cases</div>
                <div class="stat-value">{{ $kpis['total'] ?? 0 }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card dashboard-card shadow-sm kpi-active">
            <div class="card-body">
                <div class="stat-label">Active</div>
                <div class="stat-value">{{ $kpis['active'] ?? 0 }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card dashboard-card shadow-sm kpi-dormant">
            <div class="card-body">
                <div class="stat-label">Dormant</div>
                <div class="stat-value">{{ $kpis['dormant'] ?? 0 }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card dashboard-card shadow-sm kpi-closed">
            <div class="card-body">
                <div class="stat-label">Closed</div>
                <div class="stat-value">{{ $kpis['closed'] ?? 0 }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card chart-card shadow-sm">
            <div class="card-header">Monthly Intake Trend</div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="chartMonthlyTrend" width="400" height="280"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card chart-card shadow-sm">
            <div class="card-header">Cases by Category</div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="chartByCategory" width="400" height="280"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card chart-card shadow-sm">
            <div class="card-header">Upcoming Hearing Dates (Next 7 Days)</div>
            <div class="card-body py-2">
                @forelse($upcomingCases ?? [] as $case)
                    <div class="activity-item">
                        <span class="activity-dot"></span>
                        <div class="flex-grow-1">
                            <div class="activity-action">
                                {{ $case->case_number }}
                                @if($case->title) · {{ $case->title }} @endif
                                <span class="status-badge bg-{{ $case->status === 'active' ? 'warning' : ($case->status === 'dormant' ? 'secondary' : 'success') }} text-{{ $case->status === 'active' ? 'dark' : 'white' }}">{{ $case->status ?? '—' }}</span>
                            </div>
                            <div class="activity-meta">Hearing: {{ $case->hearing_date?->formatDate() ?? '—' }}</div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted small mb-0 py-3">No upcoming hearing dates in the next 7 days.</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card chart-card shadow-sm">
            <div class="card-header">Recent Import Batches</div>
            <div class="card-body py-2">
                @forelse($recentImports ?? [] as $batch)
                    <div class="activity-item">
                        <span class="activity-dot" style="background: #198754;"></span>
                        <div class="flex-grow-1">
                            <div class="activity-action">{{ $batch->source_file_name }}</div>
                            <div class="activity-meta">{{ $batch->status ?? '—' }} · {{ $batch->created_at?->diffForHumans() ?? '—' }} · by {{ $batch->creator?->name ?? 'System' }}</div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted small mb-0 py-3">No imports yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="card chart-card shadow-sm">
    <div class="card-header">Quick Actions</div>
    <div class="card-body">
        <div class="quick-action-grid">
            @can('cases.create')
                <a href="{{ route('cases.create') }}" class="btn btn-outline-primary">Register New Case</a>
            @endcan
            @can('cases.view')
                <a href="{{ route('cases.index') }}" class="btn btn-outline-secondary">Open Case List</a>
            @endcan
            @can('cases.categories.view')
                <a href="{{ route('cases.categories.index') }}" class="btn btn-outline-secondary">Manage Categories</a>
            @endcan
            @can('reports.view')
                <a href="{{ route('cases.reports') }}" class="btn btn-outline-secondary">Generate Reports</a>
            @endcan
            @can('cases.import.view')
                <a href="{{ route('cases.imports.index') }}" class="btn btn-outline-secondary">Excel Import Center</a>
            @endcan
            @can('cases.documents.recycle_bin')
                <a href="{{ route('cases.documents.recycle-bin') }}" class="btn btn-outline-secondary">Open Recycle Bin</a>
            @endcan
            @can('admin.audit.view')
                <a href="{{ route('admin.audit.index') }}" class="btn btn-outline-dark">View Audit Logs</a>
            @endcan
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const monthlyLabels = @json(array_keys($monthlyTrend ?? []));
    const monthlyValues = @json(array_values($monthlyTrend ?? []));
    const categoryLabels = @json(array_keys($casesByCategory ?? []));
    const categoryValues = @json(array_values($casesByCategory ?? []));

    const palette = ['#0d6efd', '#198754', '#fd7e14', '#6f42c1', '#20c997', '#dc3545', '#ffc107', '#6c757d'];
    const isMobile = window.matchMedia('(max-width: 991.98px)').matches;

    if (document.getElementById('chartMonthlyTrend') && monthlyLabels.length) {
        new Chart(document.getElementById('chartMonthlyTrend'), {
            type: 'line',
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: 'Cases Registered',
                    data: monthlyValues,
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    borderColor: '#0d6efd',
                    borderWidth: 2,
                    fill: true,
                    tension: .3,
                    pointRadius: 4,
                    pointBackgroundColor: '#0d6efd'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.06)' }, ticks: { precision: 0 } },
                    x: { grid: { display: false }, ticks: { maxRotation: 45 } }
                }
            }
        });
    } else if (document.getElementById('chartMonthlyTrend')) {
        document.getElementById('chartMonthlyTrend').parentElement.innerHTML = '<p class="text-muted small mb-0 d-flex align-items-center justify-content-center h-100">No data yet</p>';
    }

    if (document.getElementById('chartByCategory') && categoryLabels.length) {
        new Chart(document.getElementById('chartByCategory'), {
            type: 'doughnut',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryValues,
                    backgroundColor: palette.slice(0, categoryLabels.length),
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: isMobile ? 'bottom' : 'right' } },
                cutout: '60%'
            }
        });
    } else if (document.getElementById('chartByCategory')) {
        document.getElementById('chartByCategory').parentElement.innerHTML = '<p class="text-muted small mb-0 d-flex align-items-center justify-content-center h-100">No category data yet</p>';
    }
});
</script>
@endpush
