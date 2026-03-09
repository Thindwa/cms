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
.kpi-total { background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); color: #fff; }
.kpi-docs { background: linear-gradient(135deg, #198754 0%, #157347 100%); color: #fff; }
.kpi-notes { background: linear-gradient(135deg, #fd7e14 0%, #e8590c 100%); color: #fff; }
.kpi-uncat { background: linear-gradient(135deg, #6c757d 0%, #495057 100%); color: #fff; }
.chart-card { border: none; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
.chart-card .card-header { border-bottom: 1px solid rgba(0,0,0,.06); font-weight: 600; padding: 1rem 1.25rem; background: #fff; border-radius: 12px 12px 0 0; }
.activity-item { padding: .75rem 0; border-bottom: 1px solid rgba(0,0,0,.06); display: flex; align-items: flex-start; gap: .75rem; }
.activity-item:last-child { border-bottom: 0; }
.activity-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; margin-top: 5px; background: #0d6efd; }
.activity-meta { font-size: 0.8rem; color: #6c757d; }
.activity-action { font-weight: 500; color: #212529; }
.chart-container { position: relative; height: 280px; }
</style>
@endpush

@section('content')
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card dashboard-card shadow-sm kpi-total">
            <div class="card-body">
                <div class="stat-label">Total Cases</div>
                <div class="stat-value">{{ $kpis['total'] ?? 0 }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card dashboard-card shadow-sm kpi-docs">
            <div class="card-body">
                <div class="stat-label">With Documents</div>
                <div class="stat-value">{{ $kpis['with_documents'] ?? 0 }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card dashboard-card shadow-sm kpi-notes">
            <div class="card-body">
                <div class="stat-label">With Notes</div>
                <div class="stat-value">{{ $kpis['with_notes'] ?? 0 }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card dashboard-card shadow-sm kpi-uncat">
            <div class="card-body">
                <div class="stat-label">Uncategorized</div>
                <div class="stat-value">{{ $kpis['uncategorized'] ?? 0 }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card chart-card shadow-sm">
            <div class="card-header">Case Coverage</div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="chartCoverage" width="400" height="280"></canvas>
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

<div class="card chart-card shadow-sm">
    <div class="card-header">Recent Activity</div>
    <div class="card-body py-2">
        @forelse($recentActivity ?? [] as $log)
        <div class="activity-item">
            <span class="activity-dot"></span>
            <div class="flex-grow-1">
                <div class="activity-action">{{ $log->action }}</div>
                <div class="activity-meta">
                    {{ $log->user?->name ?? 'System' }}
                    @if(!empty($log->auditable_type) && isset($caseNumbers[$log->auditable_id]))
                        · {{ $caseNumbers[$log->auditable_id] }}
                    @endif
                    · {{ $log->created_at->diffForHumans() }}
                </div>
            </div>
        </div>
        @empty
        <p class="text-muted small mb-0 py-3">No recent activity.</p>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const coverageLabels = @json($coverageLabels ?? []);
    const coverageValues = @json($coverageValues ?? []);
    const categoryLabels = @json($categoryLabels ?? []);
    const categoryValues = @json($categoryValues ?? []);

    const coverageColors = ['#198754', '#fd7e14', '#6c757d'];
    const categoryPalette = ['#0d6efd', '#198754', '#fd7e14', '#6f42c1', '#20c997', '#dc3545', '#ffc107', '#6c757d'];

    if (document.getElementById('chartCoverage') && coverageLabels.length) {
        new Chart(document.getElementById('chartCoverage'), {
            type: 'bar',
            data: {
                labels: coverageLabels,
                datasets: [{
                    label: 'Cases',
                    data: coverageValues,
                    backgroundColor: coverageColors.slice(0, coverageValues.length),
                    borderRadius: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.06)' }, ticks: { stepSize: 1 } },
                    x: { grid: { display: false } }
                }
            }
        });
    } else if (document.getElementById('chartCoverage')) {
        document.getElementById('chartCoverage').parentElement.innerHTML = '<p class="text-muted small mb-0 d-flex align-items-center justify-content-center h-100">No case data yet</p>';
    }

    if (document.getElementById('chartByCategory') && categoryLabels.length) {
        new Chart(document.getElementById('chartByCategory'), {
            type: 'doughnut',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryValues,
                    backgroundColor: categoryPalette.slice(0, categoryLabels.length),
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right' } },
                cutout: '60%'
            }
        });
    } else if (document.getElementById('chartByCategory')) {
        document.getElementById('chartByCategory').parentElement.innerHTML = '<p class="text-muted small mb-0 d-flex align-items-center justify-content-center h-100">No category data yet</p>';
    }
});
</script>
@endpush
