<?php

namespace App\Core\Dashboard;

use App\Http\Controllers\Controller;
use App\Modules\CaseManagement\Models\CaseModel;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $baseCases = CaseModel::query()
            ->whereNotNull('case_number')
            ->where('case_number', '!=', '');
        $totalCases = (clone $baseCases)->count();

        $kpis = [
            'total' => $totalCases,
            'with_documents' => (clone $baseCases)->has('documents')->count(),
            'with_notes' => (clone $baseCases)->has('notes')->count(),
            'uncategorized' => (clone $baseCases)->where(function ($query) {
                $query->whereNull('nature_of_claim')
                    ->orWhere('nature_of_claim', '');
            })
                ->count(),
        ];

        $coverageData = [
            'With Documents' => $kpis['with_documents'],
            'With Notes' => $kpis['with_notes'],
            'Uncategorized' => $kpis['uncategorized'],
        ];

        $casesByCategory = (clone $baseCases)->pluck('nature_of_claim')
            ->map(fn ($v) => filled($v) ? trim($v) : 'Uncategorized')
            ->countBy()
            ->sortDesc()
            ->toArray();
        $upcomingCases = (clone $baseCases)
            ->whereNotNull('hearing_date')
            ->whereBetween('hearing_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->orderBy('hearing_date')
            ->limit(10)
            ->get(['id', 'case_number', 'title', 'hearing_date']);

        $coverageLabels = array_keys($coverageData);
        $coverageValues = array_values($coverageData);
        $categoryLabels = array_keys($casesByCategory);
        $categoryValues = array_values($casesByCategory);

        return view('dashboard.index', compact(
            'kpis', 'casesByCategory', 'upcomingCases',
            'coverageLabels', 'coverageValues', 'categoryLabels', 'categoryValues'
        ));
    }
}
