@extends('layouts.app')

@section('title', 'Reports')
@section('page-title', 'Reports')
@section('breadcrumbs', 'Case Management / Reports')

@push('styles')
<style>
.report-metric-card { border: 0; border-radius: .75rem; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
.report-metric-card .label { font-size: .75rem; text-transform: uppercase; letter-spacing: .03em; opacity: .85; }
.report-metric-card .value { font-size: 1.5rem; font-weight: 700; line-height: 1.1; }
.chart-wrap { position: relative; min-height: 260px; }
.report-table td, .report-table th { white-space: nowrap; }
</style>
@endpush

@section('content')
@if($errors->any())
    <div class="alert alert-danger">
        {{ $errors->first() }}
    </div>
@endif
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('cases.reports') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small">Report type</label>
                <select name="report_type" class="form-select form-select-sm" id="reportTypeSelect">
                    @foreach(($reportTypes ?? []) as $key => $label)
                        <option value="{{ $key }}" {{ ($reportType ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1 filter-period" style="display:none">
                <label class="form-label small">Quarter</label>
                <select name="quarter" class="form-select form-select-sm" id="quarterSelect">
                    <option value="">All</option>
                    <option value="1" @selected(($quarterFilter ?? '') === '1')>Q1</option>
                    <option value="2" @selected(($quarterFilter ?? '') === '2')>Q2</option>
                    <option value="3" @selected(($quarterFilter ?? '') === '3')>Q3</option>
                    <option value="4" @selected(($quarterFilter ?? '') === '4')>Q4</option>
                </select>
            </div>
            <div class="col-md-1 filter-period" style="display:none">
                <label class="form-label small">Month</label>
                <select name="month" class="form-select form-select-sm" id="monthSelect">
                    <option value="">All</option>
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" @selected(($monthFilter ?? '') === (string) $m)>{{ \Carbon\Carbon::create()->month($m)->format('M') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1 filter-period" style="display:none">
                <label class="form-label small">Year</label>
                <input type="text" name="year" id="yearSelect" class="form-control form-control-sm" list="yearList" placeholder="Type year" value="{{ $yearFilter ?? '' }}" autocomplete="off">
                <datalist id="yearList">
                    @foreach(range(now()->year, 1950) as $y)
                        <option value="{{ $y }}">
                    @endforeach
                </datalist>
            </div>
            <div class="col-md-1 filter-dates">
                <label class="form-label small">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom ?? '' }}">
            </div>
            <div class="col-md-1 filter-dates">
                <label class="form-label small">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo ?? '' }}">
            </div>
            <div class="col-md-1">
                <label class="form-label small">Date basis</label>
                <select name="date_basis" class="form-select form-select-sm">
                    @foreach(($dateBasisOptions ?? []) as $key => $label)
                        <option value="{{ $key }}" {{ ($dateBasis ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach(($statusOptions ?? []) as $status)
                        <option value="{{ $status }}" @selected(($statusFilter ?? '') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label small">Officer</label>
                <select name="officer_name" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach(($officerOptions ?? []) as $officer)
                        <option value="{{ $officer }}" @selected((string)($officerFilter ?? '') === (string)$officer)>{{ $officer }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label small">Category</label>
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach(($categoryOptions ?? []) as $cat)
                        <option value="{{ $cat->id }}" @selected((string)($categoryFilter ?? '') === (string)$cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm">Generate</button>
                <a href="{{ route('cases.reports') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

@if(isset($data))
@if(!empty($data['cards']))
<div class="row g-3 mb-3">
    @foreach($data['cards'] as $card)
        <div class="col-sm-6 col-lg-3">
            <div class="card report-metric-card {{ $card['class'] ?? 'bg-light' }}">
                <div class="card-body">
                    <div class="label">{{ $card['label'] }}</div>
                    <div class="value">{{ $card['value'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endif

@if(!empty($data['charts']))
<div class="row g-3 mb-3">
    @foreach($data['charts'] as $chart)
        <div class="col-lg-{{ count($data['charts']) <= 2 ? '6' : (count($data['charts']) > 1 ? '4' : '12') }}">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">{{ $chart['title'] }}</div>
                <div class="card-body">
                    <div class="chart-wrap">
                        <canvas id="{{ $chart['id'] }}"></canvas>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endif

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span>{{ $data['title'] }}</span>
        <div class="btn-group btn-group-sm">
            @can('reports.export')
            <a href="{{ route('cases.reports', array_merge(request()->query(), ['export' => 'pdf'])) }}" class="btn btn-outline-secondary">Export PDF</a>
            <a href="{{ route('cases.reports', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-outline-secondary">Export Excel</a>
            @endcan
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">Print</button>
        </div>
    </div>
    <div class="card-body report-body">
        <p class="text-muted small mb-2">{{ $data['subtitle'] ?? "Period: {$dateFrom} to {$dateTo}" }}</p>
        <div class="table-responsive">
        <table class="table table-sm table-bordered report-table">
            @if(!empty($data['rows']))
            <thead class="table-light">
                <tr>
                    @foreach(array_keys($data['rows'][0] ?? []) as $th)
                        <th>{{ $th }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($data['rows'] as $row)
                    <tr>
                        @foreach($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
            @else
            <tbody><tr><td class="text-muted">No data for this period.</td></tr></tbody>
            @endif
        </table>
        </div>
    </div>
</div>

@if(!empty($data['sections']))
@foreach($data['sections'] as $section)
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white">{{ $section['title'] }}</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0 report-table">
                @if(!empty($section['rows']))
                <thead class="table-light">
                    <tr>
                        @foreach(array_keys($section['rows'][0] ?? []) as $th)
                            <th>{{ $th }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($section['rows'] as $row)
                        <tr>
                            @foreach($row as $cell)
                                <td>{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
                @else
                <tbody><tr><td class="text-muted">No data available.</td></tr></tbody>
                @endif
            </table>
        </div>
    </div>
</div>
@endforeach
@endif
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const quarterStarts = @json($quarterStarts);

    function toggleFilters() {
        const type = document.getElementById('reportTypeSelect').value;
        const isMonthly = type === 'monthly';
        const isQuarterly = type === 'quarterly';
        const isPeriod = isMonthly || isQuarterly;

        const periodEls = document.querySelectorAll('.filter-period');
        const quarterEl = periodEls[0];
        const monthEl = periodEls[1];
        const yearEl = periodEls[2];

        document.querySelectorAll('.filter-dates').forEach(el => el.style.display = isPeriod ? 'none' : '');

        if (isPeriod) {
            quarterEl.style.display = isQuarterly ? '' : 'none';
            monthEl.style.display = isMonthly ? '' : 'none';
            yearEl.style.display = '';
        } else {
            quarterEl.style.display = 'none';
            monthEl.style.display = 'none';
            yearEl.style.display = 'none';
        }
    }

    document.getElementById('reportTypeSelect').addEventListener('change', toggleFilters);
    toggleFilters();

    function updateDateRange() {
        const q = document.getElementById('quarterSelect').value;
        const m = document.getElementById('monthSelect').value;
        const y = document.getElementById('yearSelect').value;
        if (!y) return;

        if (q) {
            const startMonth = quarterStarts[q];
            const nextQ = (parseInt(q) % 4) + 1;
            let endMonth = quarterStarts[nextQ] - 1;
            if (endMonth < startMonth) endMonth += 12;
            const from = new Date(parseInt(y), startMonth - 1, 1);
            const to = new Date(parseInt(y) + (endMonth > 12 ? 1 : 0), (endMonth > 12 ? endMonth - 13 : endMonth - 1) + 1, 0);
            document.querySelector('input[name="date_from"]').value = from.toISOString().slice(0, 10);
            document.querySelector('input[name="date_to"]').value = to.toISOString().slice(0, 10);
        } else if (m) {
            document.querySelector('input[name="date_from"]').value = y + '-' + String(m).padStart(2, '0') + '-01';
            const lastDay = new Date(parseInt(y), parseInt(m), 0).getDate();
            document.querySelector('input[name="date_to"]').value = y + '-' + String(m).padStart(2, '0') + '-' + String(lastDay).padStart(2, '0');
        }
    }

    document.getElementById('quarterSelect').addEventListener('change', updateDateRange);
    document.getElementById('monthSelect').addEventListener('change', updateDateRange);
    document.getElementById('yearSelect').addEventListener('change', updateDateRange);

    document.addEventListener('DOMContentLoaded', function () {
        const chartConfigs = @json($data['charts'] ?? []);
        if (!Array.isArray(chartConfigs) || chartConfigs.length === 0) {
            return;
        }

        const palette = ['#0d6efd', '#198754', '#fd7e14', '#6f42c1', '#20c997', '#dc3545', '#6c757d', '#ffc107'];
        chartConfigs.forEach((config) => {
            const canvas = document.getElementById(config.id);
            if (!canvas) return;
            new Chart(canvas, {
                type: config.type || 'bar',
                data: {
                    labels: config.labels || [],
                    datasets: [{
                        label: config.title || 'Cases',
                        data: config.values || [],
                        backgroundColor: config.colors || (config.labels || []).map((_, i) => palette[i % palette.length]),
                        borderColor: '#ffffff',
                        borderWidth: config.type === 'line' ? 2 : 1,
                        fill: config.type !== 'line',
                        tension: .3,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: config.type === 'doughnut' } },
                    scales: config.type === 'doughnut' ? {} : {
                        y: { beginAtZero: true, ticks: { precision: 0 } },
                        x: { ticks: { maxRotation: 45, minRotation: 0 } }
                    }
                }
            });
        });
    });
</script>
@endpush
