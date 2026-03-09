<?php

namespace App\Modules\CaseManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CaseManagement\Models\CaseModel;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:reports.view');
    }

    public function index(Request $request): View|StreamedResponse|BinaryFileResponse
    {
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : now()->startOfMonth();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : now();
        $reportType = $request->get('report_type', 'summary');

        $query = CaseModel::query()->whereBetween('created_at', [$dateFrom, $dateTo->endOfDay()]);

        $data = match ($reportType) {
            'by_officer' => [
                'title' => 'Cases per Officer',
                'rows' => (clone $query)->with('assignedOfficer:id,name')
                    ->get()
                    ->groupBy('assigned_to')
                    ->map(fn ($g) => ['Officer' => $g->first()->assignedOfficer?->name ?? 'Unassigned', 'Total' => $g->count()])
                    ->values()
                    ->toArray(),
            ],
            'by_category' => [
                'title' => 'Cases by Nature of Claim',
                'rows' => (clone $query)->get()
                    ->groupBy('nature_of_claim')
                    ->map(fn ($g, $cat) => ['Nature of Claim' => $cat ?? '—', 'Total' => $g->count()])
                    ->values()
                    ->toArray(),
            ],
            default => [
                'title' => 'Cases Summary',
                'rows' => $this->summaryReportRows($query),
            ],
        };

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
            'data' => $data,
        ]);
    }

    protected function summaryReportRows($query): array
    {
        $rows = [
            ['Metric' => 'Total cases', 'Value' => (clone $query)->count()],
            ['Metric' => 'With nature of claim', 'Value' => (clone $query)->whereNotNull('nature_of_claim')->count()],
            ['Metric' => 'With documents', 'Value' => (clone $query)->has('documents')->count()],
            ['Metric' => 'With notes', 'Value' => (clone $query)->has('notes')->count()],
        ];

        return $rows;
    }

    protected function exportExcel(string $title, array $rows, Carbon $dateFrom, Carbon $dateTo): StreamedResponse
    {
        $filename = 'report-' . now()->format('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        return response()->streamDownload(function () use ($title, $rows, $dateFrom, $dateTo) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [$title]);
            fputcsv($out, ['Period: ' . $dateFrom->format('Y-m-d') . ' to ' . $dateTo->format('Y-m-d')]);
            fputcsv($out, []);
            if (! empty($rows)) {
                $first = reset($rows);
                fputcsv($out, array_keys(is_array($first) ? $first : (array) $first));
                foreach ($rows as $row) {
                    fputcsv($out, is_array($row) ? $row : (array) $row);
                }
            }
            fclose($out);
        }, $filename, $headers);
    }
}
