<?php

namespace App\Modules\CaseManagement\Controllers;

use App\Core\Settings\SettingsService;
use App\Http\Controllers\Controller;
use App\Modules\CaseManagement\Models\CaseCategory;
use App\Modules\CaseManagement\Models\CaseModel;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        protected SettingsService $settings
    ) {
        $this->middleware('can:reports.view');
    }

    public function index(Request $request): View|StreamedResponse|BinaryFileResponse|RedirectResponse
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $hasDateFilter = $request->filled('date_from') && $request->filled('date_to');

        if ($hasDateFilter) {
            $inputFrom = Carbon::parse((string) $request->input('date_from'))->startOfDay();
            $inputTo = Carbon::parse((string) $request->input('date_to'))->startOfDay();
            if ($inputFrom->gt($inputTo)) {
                $query = $request->except(['date_from', 'date_to']);

                return redirect()
                    ->route('cases.reports', $query)
                    ->withErrors(['date_to' => 'Invalid date range entered.']);
            }
        }

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : null;

        $quarterFilter = $request->get('quarter');
        $monthFilter = $request->get('month');
        $yearFilter = $request->get('year');

        if ($monthFilter && $yearFilter && ! $dateFrom && ! $dateTo && ! $quarterFilter) {
            $year = (int) $yearFilter;
            $month = (int) $monthFilter;
            $dateFrom = Carbon::createFromDate($year, $month, 1)->startOfDay();
            $dateTo = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();
        }

        if ($quarterFilter && $yearFilter && ! $dateFrom && ! $dateTo && ! $monthFilter) {
            $qStarts = [
                1 => (int) $this->settings->get('q1_start_month', 1),
                2 => (int) $this->settings->get('q2_start_month', 4),
                3 => (int) $this->settings->get('q3_start_month', 7),
                4 => (int) $this->settings->get('q4_start_month', 10),
            ];
            $startMonth = $qStarts[(int) $quarterFilter];
            $nextQ = ((int) $quarterFilter % 4) + 1;
            $endMonth = $qStarts[$nextQ] - 1;
            if ($endMonth < $startMonth) {
                $endMonth += 12;
            }
            $year = (int) $yearFilter;
            $dateFrom = Carbon::createFromDate($year, $startMonth, 1)->startOfDay();
            if ($endMonth > 12) {
                $dateTo = Carbon::createFromDate($year + 1, $endMonth - 12, 1)->endOfMonth()->endOfDay();
            } else {
                $dateTo = Carbon::createFromDate($year, $endMonth, 1)->endOfMonth()->endOfDay();
            }
        }

        $allowedReportTypes = [
            'summary',
            'cases_per_officer',
            'cases_registered',
            'case_category_breakdown',
            'case_status',
            'by_officer',
            'by_category',
            'by_status',
            'aging',
            'hearing_schedule',
            'monthly',
            'quarterly',
        ];
        $reportType = in_array($request->get('report_type', 'summary'), $allowedReportTypes, true)
            ? $request->get('report_type', 'summary')
            : 'summary';
        $allowedDateBasis = ['created_at', 'date_filed', 'updated_at', 'hearing_date'];
        $dateBasis = in_array($request->get('date_basis', 'date_filed'), $allowedDateBasis, true)
            ? $request->get('date_basis', 'date_filed')
            : 'date_filed';

        $query = $this->buildBaseQuery($request, $dateFrom, $dateTo, $dateBasis);

        $data = $this->buildReportData($reportType, $query, $dateFrom, $dateTo, $request);

        $export = $request->get('export');
        if ($export === 'pdf') {
            $this->authorize('reports.export');
            $pdf = PdfFacade::loadView('case_management::reports.pdf', [
                'title' => $data['title'],
                'dateFrom' => $dateFrom?->format('Y-m-d') ?? '—',
                'dateTo' => $dateTo?->format('Y-m-d') ?? '—',
                'rows' => $data['rows'],
            ]);
            return response()->streamDownload(fn () => print($pdf->output()), 'report-' . now()->format('Y-m-d-His') . '.pdf', ['Content-Type' => 'application/pdf']);
        }
        if ($export === 'excel') {
            $this->authorize('reports.export');
            return $this->exportExcel($data['title'], $data['rows'], $dateFrom, $dateTo);
        }

        return view('case_management::reports.index', [
            'reportType' => $reportType,
            'dateFrom' => $dateFrom?->format('Y-m-d'),
            'dateTo' => $dateTo?->format('Y-m-d'),
            'dateBasis' => $dateBasis,
            'dateBasisOptions' => [
                'date_filed' => 'Date Filed',
                'created_at' => 'Created Date',
                'updated_at' => 'Last Updated',
                'hearing_date' => 'Hearing Date',
            ],
            'reportTypes' => [
                'summary' => 'Executive Summary',
                'cases_per_officer' => 'Cases Per Officer',
                'cases_registered' => 'Cases Registered',
                'case_category_breakdown' => 'Case Category Breakdown',
                'case_status' => 'Case Status Report',
                'aging' => 'Case Aging',
                'hearing_schedule' => 'Hearing Schedule',
                'monthly' => 'Monthly Intake',
                'quarterly' => 'Quarterly Intake',
            ],
            'statusFilter' => $request->get('status'),
            'officerFilter' => $request->get('officer_name'),
            'categoryFilter' => $request->get('category_id'),
            'quarterFilter' => $quarterFilter,
            'monthFilter' => $monthFilter,
            'yearFilter' => $yearFilter,
            'quarterStarts' => [
                1 => (int) $this->settings->get('q1_start_month', 1),
                2 => (int) $this->settings->get('q2_start_month', 4),
                3 => (int) $this->settings->get('q3_start_month', 7),
                4 => (int) $this->settings->get('q4_start_month', 10),
            ],
            'statusOptions' => CaseModel::query()->select('status')->distinct()->pluck('status')->filter()->sort()->values(),
            'officerOptions' => CaseModel::query()
                ->select('title')
                ->whereNotNull('title')
                ->where('title', '!=', '')
                ->distinct()
                ->orderBy('title')
                ->pluck('title')
                ->values(),
            'categoryOptions' => CaseCategory::query()->orderBy('name')->get(['id', 'name']),
            'data' => $data,
        ]);
    }

    protected function buildBaseQuery(Request $request, Carbon|null $dateFrom, Carbon|null $dateTo, string $dateBasis): Builder
    {
        $query = CaseModel::query()
            ->whereNotNull('case_number')
            ->where('case_number', '!=', '');

        if ($dateFrom && $dateTo) {
            if ($dateBasis === 'hearing_date') {
                $query->whereNotNull('hearing_date')
                    ->whereBetween('hearing_date', [$dateFrom->toDateString(), $dateTo->toDateString()]);
            } elseif ($dateBasis === 'date_filed') {
                $query->whereBetween('date_filed', [$dateFrom->toDateString(), $dateTo->toDateString()]);
            } else {
                $query->whereBetween($dateBasis, [$dateFrom->copy()->startOfDay(), $dateTo->copy()->endOfDay()]);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('officer_name')) {
            $query->where('title', $request->string('officer_name')->toString());
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->string('category_id')->toString());
        }

        return $query;
    }

    protected function buildReportData(string $reportType, Builder $query, Carbon|null $dateFrom, Carbon|null $dateTo, Request $request): array
    {
        return match ($reportType) {
            'cases_per_officer', 'by_officer' => $this->officerReportData($query),
            'cases_registered' => $this->registeredCasesReportData($query),
            'case_category_breakdown', 'by_category' => $this->categoryReportData($query),
            'case_status', 'by_status' => $this->statusReportData($query),
            'aging' => $this->agingReportData($query),
            'hearing_schedule' => $this->hearingScheduleReportData($query),
            'monthly' => $this->monthlyReportData($query, $dateFrom, $dateTo, $request),
            'quarterly' => $this->quarterlyReportData($query, $dateFrom, $dateTo, $request),
            default => $this->summaryReportData($query, $dateFrom, $dateTo),
        };
    }

    protected function summaryReportData(Builder $query, Carbon|null $dateFrom, Carbon|null $dateTo): array
    {
        $total = (clone $query)->count();
        $categorized = (clone $query)->whereNotNull('category_id')->count();
        $uncategorized = $total - $categorized;
        $withDocuments = (clone $query)->has('documents')->count();
        $withNotes = (clone $query)->has('notes')->count();
        $activeCases = (clone $query)->where('status', 'active')->count();
        $dormantCases = (clone $query)->where('status', 'dormant')->count();
        $closedCases = (clone $query)->where('status', 'closed')->count();
        $upcomingHearings = (clone $query)->whereBetween('hearing_date', [now()->toDateString(), now()->addDays(30)->toDateString()])->count();

        $categoryRows = (clone $query)
            ->leftJoin('case_categories', 'cases.category_id', '=', 'case_categories.id')
            ->selectRaw("COALESCE(case_categories.name, 'Uncategorized') as category_name")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('cases.category_id', 'case_categories.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => ['Category' => $row->category_name, 'Total' => (int) $row->total])
            ->toArray();

        $rows = [
            ['Metric' => 'Total cases', 'Value' => $total],
            ['Metric' => 'Active cases', 'Value' => $activeCases],
            ['Metric' => 'Dormant cases', 'Value' => $dormantCases],
            ['Metric' => 'Closed cases', 'Value' => $closedCases],
            ['Metric' => 'Categorized', 'Value' => $categorized],
            ['Metric' => 'Uncategorized', 'Value' => $uncategorized],
            ['Metric' => 'With documents', 'Value' => $withDocuments],
            ['Metric' => 'With notes', 'Value' => $withNotes],
            ['Metric' => 'Upcoming hearings (next 30 days)', 'Value' => $upcomingHearings],
        ];

        $statusRows = $this->statusRows($query);

        return [
            'title' => 'Executive Case Summary',
            'rows' => $rows,
            'cards' => [
                ['label' => 'Total Cases', 'value' => $total, 'class' => 'bg-primary text-white'],
                ['label' => 'Active Cases', 'value' => $activeCases, 'class' => 'bg-success text-white'],
                ['label' => 'Dormant Cases', 'value' => $dormantCases, 'class' => 'bg-secondary text-white'],
                ['label' => 'Closed Cases', 'value' => $closedCases, 'class' => 'bg-danger text-white'],
            ],
            'charts' => [
                [
                    'id' => 'statusDistribution',
                    'title' => 'Status Distribution',
                    'type' => 'doughnut',
                    'labels' => array_column($statusRows, 'Status'),
                    'values' => array_column($statusRows, 'Total'),
                    'colors' => array_map(fn ($label) => match ($label) {
                        'Active' => '#198754',   // green
                        'Closed' => '#dc3545',   // red
                        'Dormant' => '#6c757d',  // gray
                        default => '#0d6efd',    // blue
                    }, array_column($statusRows, 'Status')),
                ],
                [
                    'id' => 'categorySplit',
                    'title' => 'Category Split',
                    'type' => 'doughnut',
                    'labels' => array_column($categoryRows, 'Category'),
                    'values' => array_column($categoryRows, 'Total'),
                ],
            ],
            'sections' => [
                ['title' => 'By Status', 'rows' => $statusRows],
            ],
            'subtitle' => $dateFrom && $dateTo ? 'Period: ' . $dateFrom->formatDate() . ' to ' . $dateTo->formatDate() : 'All time',
        ];
    }

    protected function officerReportData(Builder $query): array
    {
        $now = now();
        $rows = (clone $query)
            ->selectRaw("COALESCE(NULLIF(TRIM(title), ''), 'Unassigned') as officer_name")
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active")
            ->selectRaw("SUM(CASE WHEN status = 'dormant' THEN 1 ELSE 0 END) as dormant")
            ->selectRaw("SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed")
            ->selectRaw("SUM(CASE WHEN hearing_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as upcoming", [
                $now->startOfDay()->toDateString(),
                $now->copy()->addDays(30)->endOfDay()->toDateString(),
            ])
            ->groupBy(DB::raw("COALESCE(NULLIF(TRIM(title), ''), 'Unassigned')"))
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'Officer' => $row->officer_name,
                'Cases' => (int) $row->total,
                'Active' => (int) $row->active,
                'Dormant' => (int) $row->dormant,
                'Closed' => (int) $row->closed,
                'Upcoming Hearings (30d)' => (int) $row->upcoming,
            ])
            ->toArray();

        return [
            'title' => 'Report: Number of Cases Per Officer',
            'rows' => $rows,
            'charts' => [[
                'id' => 'officerWorkload',
                'title' => 'Cases per Officer',
                'type' => 'bar',
                'labels' => array_column($rows, 'Officer'),
                'values' => array_column($rows, 'Cases'),
            ]],
        ];
    }

    protected function registeredCasesReportData(Builder $query): array
    {
        $rows = (clone $query)
            ->orderBy('created_at', 'desc')
            ->limit(1000)
            ->get()
            ->map(function (CaseModel $case) {
                return [
                    'Case No' => $case->case_number,
                    'Registered Date' => optional($case->created_at)->formatDate() ?? '—',
                    'Date Filed' => optional($case->date_filed)->formatDate() ?? '—',
                    'Officer' => $case->title ?: 'Unassigned',
                    'Status' => $case->status ?: 'Unknown',
                ];
            })
            ->toArray();

        return [
            'title' => 'Report: Cases Registered',
            'rows' => $rows,
            'subtitle' => 'Filtered by selected date range and filters.',
        ];
    }

    protected function categoryReportData(Builder $query): array
    {
        $rows = (clone $query)
            ->leftJoin('case_categories', 'cases.category_id', '=', 'case_categories.id')
            ->selectRaw("COALESCE(case_categories.name, 'Uncategorized') as category_name")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('cases.category_id', 'case_categories.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'Category' => $row->category_name,
                'Total' => (int) $row->total,
            ])
            ->toArray();

        return [
            'title' => 'Cases by Category',
            'rows' => $rows,
            'charts' => [[
                'id' => 'claimCategory',
                'title' => 'Category Split',
                'type' => 'doughnut',
                'labels' => array_column($rows, 'Category'),
                'values' => array_column($rows, 'Total'),
            ]],
        ];
    }

    protected function statusRows(Builder $query): array
    {
        $total = max((clone $query)->count(), 1);
        $rows = (clone $query)
            ->selectRaw("COALESCE(NULLIF(status, ''), 'Unknown') as status_name")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        return $rows->map(fn ($row) => [
            'Status' => ucfirst((string) $row->status_name),
            'Total' => (int) $row->total,
            'Share %' => round(((int) $row->total / $total) * 100, 2),
        ])->toArray();
    }

    protected function statusReportData(Builder $query): array
    {
        $rows = $this->statusRows($query);
        return [
            'title' => 'Case Status Distribution',
            'rows' => $rows,
            'charts' => [[
                'id' => 'statusOverview',
                'title' => 'Status Overview',
                'type' => 'doughnut',
                'labels' => array_column($rows, 'Status'),
                'values' => array_column($rows, 'Total'),
            ]],
        ];
    }

    protected function agingReportData(Builder $query): array
    {
        $buckets = [
            '0-30 days' => ['min' => 0, 'max' => 30],
            '31-90 days' => ['min' => 31, 'max' => 90],
            '91-180 days' => ['min' => 91, 'max' => 180],
            '181-365 days' => ['min' => 181, 'max' => 365],
            '365+ days' => ['min' => 366, 'max' => null],
        ];
        $rows = collect(array_keys($buckets))->mapWithKeys(fn ($bucket) => [$bucket => ['Bucket' => $bucket, 'Total' => 0, 'Active' => 0, 'Dormant' => 0, 'Closed' => 0]])->toArray();

        (clone $query)->get(['status', 'date_filed', 'created_at'])->each(function (CaseModel $case) use (&$rows, $buckets) {
            $start = $case->date_filed ?? optional($case->created_at)?->startOfDay();
            if (! $start) {
                return;
            }
            $days = $start->diffInDays(now()->startOfDay());
            foreach ($buckets as $label => $range) {
                $max = $range['max'];
                if ($days >= $range['min'] && ($max === null || $days <= $max)) {
                    $rows[$label]['Total']++;
                    if ($case->status === 'closed') {
                        $rows[$label]['Closed']++;
                    } elseif ($case->status === 'dormant') {
                        $rows[$label]['Dormant']++;
                    } else {
                        $rows[$label]['Active']++;
                    }
                    break;
                }
            }
        });

        $rows = array_values($rows);
        return [
            'title' => 'Case Aging Report',
            'rows' => $rows,
            'charts' => [[
                'id' => 'agingOverview',
                'title' => 'Aging Buckets',
                'type' => 'bar',
                'labels' => array_column($rows, 'Bucket'),
                'values' => array_column($rows, 'Total'),
            ]],
        ];
    }

    protected function hearingScheduleReportData(Builder $query): array
    {
        $rows = (clone $query)
            ->whereNotNull('hearing_date')
            ->orderBy('hearing_date')
            ->limit(500)
            ->get()
            ->map(fn (CaseModel $case) => [
                'Hearing Date' => $case->hearing_date?->formatDate() ?? '—',
                'Case No' => $case->case_number,
                'AG Reference' => $case->reference_number ?? '—',
                'Officer Dealing' => $case->title ?: 'Unassigned',
                'Claimant' => $case->claimant ?? '—',
                'Defendant' => $case->defendant ?? '—',
                'Status' => $case->status ?? '—',
            ])
            ->toArray();

        return [
            'title' => 'Hearing Schedule',
            'rows' => $rows,
        ];
    }

    protected function monthlyReportData(Builder $query, Carbon|null $dateFrom, Carbon|null $dateTo, Request $request): array
    {
        $categories = CaseCategory::query()->orderBy('name')->pluck('name')->toArray();
        $dateField = $request->get('date_basis', 'created_at');
        $driver = $query->getConnection()->getDriverName();
        $monthExpr = $driver === 'mysql'
            ? "DATE_FORMAT($dateField, '%Y-%m')"
            : "strftime('%Y-%m', $dateField)";

        $grouped = (clone $query)
            ->leftJoin('case_categories', 'cases.category_id', '=', 'case_categories.id')
            ->select([DB::raw("$monthExpr as month_group"), 'case_categories.name as category_name'])
            ->get()
            ->groupBy(fn ($row) => $row->month_group ?? 'Unknown')
            ->sortKeys();

        $rows = [];
        foreach ($grouped as $period => $cases) {
            $row = ['MONTH' => $period];
            $categorizedTotal = 0;
            foreach ($categories as $cat) {
                $count = $cases->filter(fn ($c) => ($c->category_name ?? 'Uncategorized') === $cat)->count();
                $row[$cat] = $count;
                $categorizedTotal += $count;
            }
            $uncategorized = $cases->count() - $categorizedTotal;
            $row['UNCATEGORIZED'] = $uncategorized;
            $row['TOTAL'] = $cases->count();
            $rows[] = $row;
        }

        return [
            'title' => 'Monthly Intake by Category',
            'rows' => $rows,
            'charts' => [[
                'id' => 'monthlyBreakdown',
                'title' => 'Monthly Intake',
                'type' => 'bar',
                'labels' => array_column($rows, 'MONTH'),
                'values' => array_column($rows, 'TOTAL'),
            ]],
            'subtitle' => $dateFrom && $dateTo ? 'Period: ' . $dateFrom->formatDate() . ' to ' . $dateTo->formatDate() : 'All time',
        ];
    }

    protected function quarterlyReportData(Builder $query, Carbon|null $dateFrom, Carbon|null $dateTo, Request $request): array
    {
        $categories = CaseCategory::query()->orderBy('name')->pluck('name')->toArray();
        $dateField = $request->get('date_basis', 'created_at');
        $qStarts = [
            1 => (int) $this->settings->get('q1_start_month', 1),
            2 => (int) $this->settings->get('q2_start_month', 4),
            3 => (int) $this->settings->get('q3_start_month', 7),
            4 => (int) $this->settings->get('q4_start_month', 10),
        ];

        $orderedStarts = $qStarts;
        asort($orderedStarts);
        $orderedNums = array_keys($orderedStarts);
        $orderedMonths = array_values($orderedStarts);
        $firstStart = $orderedMonths[0];

        $grouped = (clone $query)
            ->leftJoin('case_categories', 'cases.category_id', '=', 'case_categories.id')
            ->select([$dateField, 'case_categories.name as category_name'])
            ->get()
            ->groupBy(function ($row) use ($dateField, $orderedNums, $orderedMonths, $firstStart) {
                $date = $row->{$dateField};
                if (! $date) {
                    return 'Unknown';
                }
                $date = Carbon::parse($date);
                $month = (int) $date->format('n');
                $year = (int) $date->format('Y');

                $adjusted = ($month - $firstStart + 12) % 12;
                $index = intdiv($adjusted, 3);
                $q = $orderedNums[$index] ?? 4;
                return "Q{$q} {$year}";
            })
            ->sortKeys();

        $rows = [];
        foreach ($grouped as $period => $cases) {
            $row = ['QUARTER' => $period];
            $categorizedTotal = 0;
            foreach ($categories as $cat) {
                $count = $cases->filter(fn ($c) => ($c->category_name ?? 'Uncategorized') === $cat)->count();
                $row[$cat] = $count;
                $categorizedTotal += $count;
            }
            $uncategorized = $cases->count() - $categorizedTotal;
            $row['UNCATEGORIZED'] = $uncategorized;
            $row['TOTAL'] = $cases->count();
            $rows[] = $row;
        }

        $subtitle = $dateFrom && $dateTo
            ? 'Period: ' . $dateFrom->formatDate() . ' to ' . $dateTo->formatDate()
            : 'All time';

        return [
            'title' => 'Quarterly Intake by Category',
            'rows' => $rows,
            'charts' => [[
                'id' => 'quarterlyBreakdown',
                'title' => 'Quarterly Intake',
                'type' => 'bar',
                'labels' => array_column($rows, 'QUARTER'),
                'values' => array_column($rows, 'TOTAL'),
            ]],
            'subtitle' => $subtitle,
        ];
    }

    protected function exportExcel(string $title, array $rows, Carbon|null $dateFrom, Carbon|null $dateTo): StreamedResponse
    {
        $filename = 'report-' . now()->format('Y-m-d-His') . '.xlsx';
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        return response()->streamDownload(function () use ($title, $rows, $dateFrom, $dateTo) {
            $sheet = new Spreadsheet();
            $active = $sheet->getActiveSheet();
            $active->setTitle('Report');
            $active->setCellValue('A1', $title);
            $active->setCellValue('A2', $dateFrom && $dateTo ? 'Period: ' . $dateFrom->format('Y-m-d') . ' to ' . $dateTo->format('Y-m-d') : 'All time');
            $rowIndex = 4;

            if (! empty($rows)) {
                $first = reset($rows);
                $headersRow = array_keys(is_array($first) ? $first : (array) $first);
                $column = 1;
                foreach ($headersRow as $header) {
                    $active->setCellValueByColumnAndRow($column, $rowIndex, (string) $header);
                    $column++;
                }
                $rowIndex++;

                foreach ($rows as $row) {
                    $column = 1;
                    foreach ((is_array($row) ? $row : (array) $row) as $cell) {
                        $active->setCellValueByColumnAndRow($column, $rowIndex, is_scalar($cell) || $cell === null ? (string) $cell : json_encode($cell));
                        $column++;
                    }
                    $rowIndex++;
                }
            }

            $writer = new Xlsx($sheet);
            $writer->save('php://output');
        }, $filename, $headers);
    }
}
