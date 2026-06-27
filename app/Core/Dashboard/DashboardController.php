<?php

namespace App\Core\Dashboard;

use App\Http\Controllers\Controller;
use App\Modules\CaseManagement\Models\CaseImportBatch;
use App\Modules\CaseManagement\Models\CaseModel;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): View
    {
        $baseCases = CaseModel::query()
            ->whereNotNull('case_number')
            ->where('case_number', '!=', '');

        $driver = $baseCases->getConnection()->getDriverName();

        // Single query for all KPI counts
        $kpi = (clone $baseCases)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active")
            ->selectRaw("SUM(CASE WHEN status = 'dormant' THEN 1 ELSE 0 END) as dormant")
            ->selectRaw("SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed")
            ->selectRaw("SUM(CASE WHEN category_id IS NULL THEN 1 ELSE 0 END) as uncategorized")
            ->selectRaw("SUM(CASE WHEN EXISTS (SELECT 1 FROM case_documents WHERE case_id = cases.id) THEN 1 ELSE 0 END) as with_documents")
            ->selectRaw("SUM(CASE WHEN EXISTS (SELECT 1 FROM case_notes WHERE case_id = cases.id) THEN 1 ELSE 0 END) as with_notes")
            ->first();

        $kpis = [
            'total' => (int) ($kpi->total ?? 0),
            'active' => (int) ($kpi->active ?? 0),
            'dormant' => (int) ($kpi->dormant ?? 0),
            'closed' => (int) ($kpi->closed ?? 0),
            'with_documents' => (int) ($kpi->with_documents ?? 0),
            'with_notes' => (int) ($kpi->with_notes ?? 0),
            'uncategorized' => (int) ($kpi->uncategorized ?? 0),
        ];

        // Group by category at the DB level
        $casesByCategory = (clone $baseCases)
            ->leftJoin('case_categories', 'cases.category_id', '=', 'case_categories.id')
            ->selectRaw("COALESCE(case_categories.name, 'Uncategorized') as category_name")
            ->selectRaw('COUNT(*) as count')
            ->groupBy(DB::raw('cases.category_id'))
            ->orderByDesc('count')
            ->pluck('count', 'category_name')
            ->toArray();

        $upcomingCases = (clone $baseCases)
            ->whereNotNull('hearing_date')
            ->whereBetween('hearing_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->orderBy('hearing_date')
            ->limit(10)
            ->get(['id', 'case_number', 'title', 'hearing_date', 'status']);

        // Group by month at the DB level
        $monthExpr = $driver === 'mysql'
            ? "DATE_FORMAT(created_at, '%Y-%m')"
            : "strftime('%Y-%m', created_at)";

        $monthlyTrend = (clone $baseCases)
            ->whereNotNull('created_at')
            ->selectRaw("{$monthExpr} as month")
            ->selectRaw('COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $recentImports = CaseImportBatch::with('creator')
            ->select(['id', 'source_file_name', 'status', 'created_at', 'created_by'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('dashboard.index', compact(
            'kpis', 'casesByCategory', 'upcomingCases',
            'monthlyTrend', 'recentImports'
        ));
    }
}
