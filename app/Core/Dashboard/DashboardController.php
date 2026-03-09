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
        $totalCases = CaseModel::count();

        $kpis = [
            'total' => $totalCases,
            'with_documents' => CaseModel::has('documents')->count(),
            'with_notes' => CaseModel::has('notes')->count(),
            'uncategorized' => CaseModel::whereNull('nature_of_claim')
                ->orWhere('nature_of_claim', '')
                ->count(),
        ];

        $coverageData = [
            'With Documents' => $kpis['with_documents'],
            'With Notes' => $kpis['with_notes'],
            'Uncategorized' => $kpis['uncategorized'],
        ];

        $casesByCategory = CaseModel::pluck('nature_of_claim')
            ->map(fn ($v) => filled($v) ? trim($v) : 'Uncategorized')
            ->countBy()
            ->sortDesc()
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

        $coverageLabels = array_keys($coverageData);
        $coverageValues = array_values($coverageData);
        $categoryLabels = array_keys($casesByCategory);
        $categoryValues = array_values($casesByCategory);

        return view('dashboard.index', compact(
            'kpis', 'casesByCategory', 'recentActivity', 'caseNumbers',
            'coverageLabels', 'coverageValues', 'categoryLabels', 'categoryValues'
        ));
    }
}
