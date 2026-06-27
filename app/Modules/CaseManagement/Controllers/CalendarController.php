<?php

namespace App\Modules\CaseManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CaseManagement\Models\CaseModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:cases.view');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->filled('start')) {
            return $this->events($request);
        }

        return view('case_management::cases.calendar');
    }

    protected function events(Request $request): JsonResponse
    {
        $start = $request->input('start');
        $end = $request->input('end');

        $query = CaseModel::query()
            ->whereNotNull('hearing_date')
            ->whereNotNull('case_number')
            ->where('case_number', '!=', '');

        if ($start && $end) {
            $query->whereBetween('hearing_date', [$start, $end]);
        }

        $cases = $query->get(['id', 'case_number', 'title', 'hearing_date', 'status', 'claimant', 'defendant']);

        $events = $cases->map(function (CaseModel $case) {
            return [
                'id' => $case->id,
                'title' => $case->case_number . ' — ' . ($case->claimant ?? $case->defendant ?? 'N/A'),
                'start' => $case->hearing_date?->format('Y-m-d'),
                'url' => route('cases.show', $case),
                'className' => 'bg-' . ($case->status === 'active' ? 'warning' : ($case->status === 'dormant' ? 'secondary' : 'success')) . ' border-0 text-dark',
                'extendedProps' => [
                    'case_number' => $case->case_number,
                    'officer' => $case->title,
                    'status' => $case->status,
                ],
            ];
        });

        return response()->json($events);
    }
}
