<?php

namespace App\Core\Dashboard;

use App\Core\Audit\AuditLog;
use App\Http\Controllers\Controller;
use App\Modules\CaseManagement\Models\CaseModel;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $kpis = [
            'total' => CaseModel::count(),
            'open' => CaseModel::where('status', 'open')->count(),
            'in_progress' => CaseModel::where('status', 'in_progress')->count(),
            'closed' => CaseModel::where('status', 'closed')->count(),
        ];

        $casesByStatus = CaseModel::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $casesByCategory = CaseModel::query()
            ->selectRaw("coalesce(nullif(trim(nature_of_claim), ''), 'Uncategorized') as cat")
            ->selectRaw('count(*) as total')
            ->groupByRaw("coalesce(nullif(trim(nature_of_claim), ''), 'Uncategorized')")
            ->orderByDesc('total')
            ->pluck('total', 'cat')
            ->toArray();

        $recentActivity = AuditLog::with('user')
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        $caseNumbers = [];
        $caseIds = $recentActivity->where('auditable_type', CaseModel::class)->pluck('auditable_id')->unique()->filter()->values();
        if ($caseIds->isNotEmpty()) {
            $caseNumbers = CaseModel::whereIn('id', $caseIds)->pluck('case_number', 'id')->toArray();
        }

        $statusLabels = array_map(
            fn (string $k): string => ucfirst(str_replace('_', ' ', $k)),
            array_keys($casesByStatus)
        );
        $statusValues = array_values($casesByStatus);
        $categoryLabels = array_keys($casesByCategory);
        $categoryValues = array_values($casesByCategory);

        return view('dashboard.index', compact(
            'kpis', 'casesByStatus', 'casesByCategory', 'recentActivity', 'caseNumbers',
            'statusLabels', 'statusValues', 'categoryLabels', 'categoryValues'
        ));
    }
}
