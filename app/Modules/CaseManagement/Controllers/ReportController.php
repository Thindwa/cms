<?php

namespace App\Modules\CaseManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CaseManagement\Models\CaseModel;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:reports.view');
    }

    public function index(Request $request): View|StreamedResponse|BinaryFileResponse|RedirectResponse
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $inputFrom = Carbon::parse((string) $request->input('date_from'))->startOfDay();
            $inputTo = Carbon::parse((string) $request->input('date_to'))->startOfDay();
            if ($inputFrom->gt($inputTo)) {
                $query = $request->except(['date_from', 'date_to']);

                return redirect()
                    ->route('cases.reports', $query)
                    ->withErrors(['date_to' => 'Invalid date range entered.']);
            }
        }

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : now()->startOfMonth();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : now();
        $allowedReportTypes = [
            'summary',
            'cases_per_officer',
            'cases_registered',
            'case_category_breakdown',
            'case_status',
            'by_officer',
            'by_category',
            'by_status',
            'by_priority',
            'aging',
            'hearing_schedule',
        ];
        $reportType = in_array($request->get('report_type', 'summary'), $allowedReportTypes, true)
            ? $request->get('report_type', 'summary')
            : 'summary';
        $allowedDateBasis = ['created_at', 'date_filed', 'updated_at', 'hearing_date'];
        $dateBasis = in_array($request->get('date_basis', 'created_at'), $allowedDateBasis, true)
            ? $request->get('date_basis', 'created_at')
            : 'created_at';

        $query = $this->buildBaseQuery($request, $dateFrom, $dateTo, $dateBasis);

        $data = $this->buildReportData($reportType, $query, $dateFrom, $dateTo);

        $export = $request->get('export');
        if ($export === 'pdf') {
            $this->authorize('reports.export');
            $pdf = PdfFacade::loadView('case_management::reports.pdf', [
                'title' => $data['title'],
                'dateFrom' => $dateFrom->format('Y-m-d'),
                'dateTo' => $dateTo->format('Y-m-d'),
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
            'dateFrom' => $dateFrom->format('Y-m-d'),
            'dateTo' => $dateTo->format('Y-m-d'),
            'dateBasis' => $dateBasis,
            'dateBasisOptions' => [
                'created_at' => 'Created Date',
                'date_filed' => 'Date Filed',
                'updated_at' => 'Last Updated',
                'hearing_date' => 'Hearing Date',
            ],
            'reportTypes' => [
                'summary' => 'Executive Summary',
                'cases_per_officer' => 'Cases Per Officer',
                'cases_registered' => 'Cases Registered',
                'case_category_breakdown' => 'Case Category Breakdown',
                'case_status' => 'Case Status Report',
                'by_priority' => 'Priority Distribution',
                'aging' => 'Case Aging',
                'hearing_schedule' => 'Hearing Schedule',
            ],
            'statusFilter' => $request->get('status'),
            'priorityFilter' => $request->get('priority'),
            'officerFilter' => $request->get('officer_name'),
            'statusOptions' => CaseModel::query()->select('status')->distinct()->pluck('status')->filter()->sort()->values(),
            'priorityOptions' => collect(range(1, 10)),
            'officerOptions' => CaseModel::query()
                ->select('title')
                ->whereNotNull('title')
                ->where('title', '!=', '')
                ->distinct()
                ->orderBy('title')
                ->pluck('title')
                ->values(),
            'data' => $data,
        ]);
    }

    protected function buildBaseQuery(Request $request, Carbon $dateFrom, Carbon $dateTo, string $dateBasis): Builder
    {
        $query = CaseModel::query()
            ->whereNotNull('case_number')
            ->where('case_number', '!=', '');

        if (in_array($dateBasis, ['date_filed', 'hearing_date'], true)) {
            $query->whereNotNull($dateBasis)
                ->whereBetween($dateBasis, [$dateFrom->toDateString(), $dateTo->toDateString()]);
        } else {
            $query->whereBetween($dateBasis, [$dateFrom->copy()->startOfDay(), $dateTo->copy()->endOfDay()]);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('priority')) {
            $query->where('priority', (int) $request->input('priority'));
        }
        if ($request->filled('officer_name')) {
            $query->where('title', $request->string('officer_name')->toString());
        }

        return $query;
    }

    protected function buildReportData(string $reportType, Builder $query, Carbon $dateFrom, Carbon $dateTo): array
    {
        return match ($reportType) {
            'cases_per_officer', 'by_officer' => $this->officerReportData($query),
            'cases_registered' => $this->registeredCasesReportData($query),
            'case_category_breakdown', 'by_category' => $this->categoryReportData($query),
            'case_status', 'by_status' => $this->statusReportData($query),
            'by_priority' => $this->priorityReportData($query),
            'aging' => $this->agingReportData($query),
            'hearing_schedule' => $this->hearingScheduleReportData($query),
            default => $this->summaryReportData($query, $dateFrom, $dateTo),
        };
    }

    protected function summaryReportData(Builder $query, Carbon $dateFrom, Carbon $dateTo): array
    {
        $total = (clone $query)->count();
        $withNature = (clone $query)->whereNotNull('nature_of_claim')->where('nature_of_claim', '!=', '')->count();
        $withDocuments = (clone $query)->has('documents')->count();
        $withNotes = (clone $query)->has('notes')->count();
        $openCases = (clone $query)->where('status', '!=', 'closed')->count();
        $closedCases = (clone $query)->where('status', 'closed')->count();
        $highPriority = (clone $query)->where('priority', '>=', 8)->count();
        $upcomingHearings = (clone $query)->whereBetween('hearing_date', [now()->toDateString(), now()->addDays(30)->toDateString()])->count();

        $rows = [
            ['Metric' => 'Total cases', 'Value' => $total],
            ['Metric' => 'Open cases', 'Value' => $openCases],
            ['Metric' => 'Closed cases', 'Value' => $closedCases],
            ['Metric' => 'High priority', 'Value' => $highPriority],
            ['Metric' => 'With nature of claim', 'Value' => $withNature],
            ['Metric' => 'With documents', 'Value' => $withDocuments],
            ['Metric' => 'With notes', 'Value' => $withNotes],
            ['Metric' => 'Upcoming hearings (next 30 days)', 'Value' => $upcomingHearings],
        ];

        $statusRows = $this->statusRows($query);
        $priorityRows = $this->priorityRows($query);

        $trendRows = (clone $query)->get(['date_filed', 'created_at'])
            ->map(function (CaseModel $case) {
                $basis = $case->date_filed ?? optional($case->created_at)?->startOfDay();
                return $basis ? $basis->format('Y-m') : null;
            })
            ->filter()
            ->countBy()
            ->sortKeys()
            ->map(fn ($count, $month) => ['Month' => $month, 'Total' => $count])
            ->values()
            ->toArray();

        return [
            'title' => 'Executive Case Summary',
            'rows' => $rows,
            'cards' => [
                ['label' => 'Total Cases', 'value' => $total, 'class' => 'bg-primary text-white'],
                ['label' => 'Open Cases', 'value' => $openCases, 'class' => 'bg-warning text-dark'],
                ['label' => 'Closed Cases', 'value' => $closedCases, 'class' => 'bg-success text-white'],
                ['label' => 'High Priority', 'value' => $highPriority, 'class' => 'bg-danger text-white'],
            ],
            'charts' => [
                [
                    'id' => 'statusDistribution',
                    'title' => 'Status Distribution',
                    'type' => 'doughnut',
                    'labels' => array_column($statusRows, 'Status'),
                    'values' => array_column($statusRows, 'Total'),
                ],
                [
                    'id' => 'priorityDistribution',
                    'title' => 'Priority Distribution',
                    'type' => 'bar',
                    'labels' => array_column($priorityRows, 'Priority'),
                    'values' => array_column($priorityRows, 'Total'),
                ],
                [
                    'id' => 'monthlyTrend',
                    'title' => 'Monthly Intake Trend',
                    'type' => 'line',
                    'labels' => array_column($trendRows, 'Month'),
                    'values' => array_column($trendRows, 'Total'),
                ],
            ],
            'sections' => [
                ['title' => 'By Status', 'rows' => $statusRows],
                ['title' => 'By Priority', 'rows' => $priorityRows],
            ],
            'subtitle' => 'Period: ' . $dateFrom->format('Y-m-d') . ' to ' . $dateTo->format('Y-m-d'),
        ];
    }

    protected function officerReportData(Builder $query): array
    {
        $rows = (clone $query)
            ->get()
            ->groupBy(fn (CaseModel $case) => filled($case->title) ? trim((string) $case->title) : 'Unassigned')
            ->map(function (Collection $cases, string $officer) {
                $upcoming = $cases->filter(fn (CaseModel $case) => $case->hearing_date && $case->hearing_date->between(now()->startOfDay(), now()->addDays(30)->endOfDay()))->count();
                return [
                    'Officer' => $officer,
                    'Cases' => $cases->count(),
                    'Open' => $cases->where('status', '!=', 'closed')->count(),
                    'Closed' => $cases->where('status', 'closed')->count(),
                    'High Priority' => $cases->where('priority', '>=', 8)->count(),
                    'Upcoming Hearings (30d)' => $upcoming,
                ];
            })
            ->sortByDesc('Cases')
            ->values()
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
                    'Registered Date' => optional($case->created_at)->format('Y-m-d') ?? '—',
                    'Date Filed' => optional($case->date_filed)->format('Y-m-d') ?? '—',
                    'Officer' => $case->title ?: 'Unassigned',
                    'Status' => $case->status ?: 'Unknown',
                    'Priority' => $case->priority ?? '—',
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
        $rows = (clone $query)->get()
            ->groupBy(fn (CaseModel $case) => filled($case->nature_of_claim) ? trim((string) $case->nature_of_claim) : 'Uncategorized')
            ->map(fn (Collection $cases, string $category) => [
                'Nature of Claim' => $category,
                'Total' => $cases->count(),
            ])
            ->sortByDesc('Total')
            ->values()
            ->toArray();

        return [
            'title' => 'Cases by Nature of Claim',
            'rows' => $rows,
            'charts' => [[
                'id' => 'claimCategory',
                'title' => 'Nature of Claim Split',
                'type' => 'doughnut',
                'labels' => array_column($rows, 'Nature of Claim'),
                'values' => array_column($rows, 'Total'),
            ]],
        ];
    }

    protected function statusRows(Builder $query): array
    {
        $total = max((clone $query)->count(), 1);
        return (clone $query)->get()
            ->groupBy(fn (CaseModel $case) => filled($case->status) ? ucfirst((string) $case->status) : 'Unknown')
            ->map(fn (Collection $cases, string $status) => [
                'Status' => $status,
                'Total' => $cases->count(),
                'Share %' => round(($cases->count() / $total) * 100, 2),
            ])
            ->sortByDesc('Total')
            ->values()
            ->toArray();
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

    protected function priorityRows(Builder $query): array
    {
        $total = max((clone $query)->count(), 1);
        return (clone $query)->get()
            ->groupBy(fn (CaseModel $case) => filled($case->priority) ? ucfirst((string) $case->priority) : 'Unknown')
            ->map(fn (Collection $cases, string $priority) => [
                'Priority' => $priority,
                'Total' => $cases->count(),
                'Share %' => round(($cases->count() / $total) * 100, 2),
            ])
            ->sortByDesc('Total')
            ->values()
            ->toArray();
    }

    protected function priorityReportData(Builder $query): array
    {
        $rows = $this->priorityRows($query);
        return [
            'title' => 'Priority Distribution',
            'rows' => $rows,
            'charts' => [[
                'id' => 'priorityOverview',
                'title' => 'Priority Overview',
                'type' => 'bar',
                'labels' => array_column($rows, 'Priority'),
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
        $rows = collect(array_keys($buckets))->mapWithKeys(fn ($bucket) => [$bucket => ['Bucket' => $bucket, 'Total' => 0, 'Open' => 0, 'Closed' => 0]])->toArray();

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
                    } else {
                        $rows[$label]['Open']++;
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
                'Hearing Date' => $case->hearing_date?->format('Y-m-d') ?? '—',
                'Case No' => $case->case_number,
                'AG Reference' => $case->reference_number ?? '—',
                'Officer Dealing' => $case->title ?: 'Unassigned',
                'Claimant' => $case->claimant ?? '—',
                'Defendant' => $case->defendant ?? '—',
                'Status' => $case->status ?? '—',
                'Priority' => $case->priority ?? '—',
            ])
            ->toArray();

        return [
            'title' => 'Hearing Schedule',
            'rows' => $rows,
        ];
    }

    protected function exportExcel(string $title, array $rows, Carbon $dateFrom, Carbon $dateTo): StreamedResponse
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
            $active->setCellValue('A2', 'Period: ' . $dateFrom->format('Y-m-d') . ' to ' . $dateTo->format('Y-m-d'));
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
