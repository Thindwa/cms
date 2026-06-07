<?php

namespace App\Modules\CaseManagement\Controllers;

use App\Core\Audit\AuditService;
use App\Http\Controllers\Controller;
use App\Modules\CaseManagement\Models\CaseModel;
use App\Modules\CaseManagement\Requests\StoreCaseRequest;
use App\Modules\CaseManagement\Requests\UpdateCaseRequest;
use App\Modules\CaseManagement\Services\CaseManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CaseController extends Controller
{
    public function __construct(
        protected CaseManagementService $caseService,
        protected AuditService $auditService,
    ) {
        $this->authorizeResource(CaseModel::class, 'case');
    }

    public function index(Request $request): View
    {
        $query = CaseModel::query()
            ->with(['createdByUser'])
            ->whereNotNull('case_number')
            ->where('case_number', '!=', '');

        if ($request->filled('case_number')) {
            $query->where('case_number', 'like', '%' . $request->case_number . '%');
        }
        if ($request->filled('case_title')) {
            $query->where('case_title', 'like', '%' . $request->case_title . '%');
        }
        if ($request->filled('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }
        if ($request->filled('reference_number')) {
            $query->where('reference_number', 'like', '%' . $request->reference_number . '%');
        }
        if ($request->filled('civil_case_number')) {
            $query->where('civil_case_number', 'like', '%' . $request->civil_case_number . '%');
        }
        if ($request->filled('party')) {
            $query->where(function ($q) use ($request) {
                $q->where('claimant', 'like', '%' . $request->party . '%')
                    ->orWhere('defendant', 'like', '%' . $request->party . '%');
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('date_filed', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date_filed', '<=', $request->date_to);
        }
        if ($request->filled('hearing_date_from')) {
            $query->whereDate('hearing_date', '>=', $request->hearing_date_from);
        }
        if ($request->filled('hearing_date_to')) {
            $query->whereDate('hearing_date', '<=', $request->hearing_date_to);
        }

        $filterKeys = [
            'case_number',
            'case_title',
            'title',
            'reference_number',
            'civil_case_number',
            'party',
            'date_from',
            'date_to',
            'hearing_date_from',
            'hearing_date_to',
        ];
        $activeFilters = collect($filterKeys)
            ->filter(fn (string $key) => $request->filled($key))
            ->mapWithKeys(fn (string $key) => [$key => (string) $request->input($key)])
            ->all();
        if ($activeFilters !== []) {
            $this->auditService->log(
                action: 'case.search',
                oldValues: null,
                newValues: ['filters' => $activeFilters]
            );
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = strtolower($request->get('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSort = ['case_number', 'case_title', 'reference_number', 'civil_case_number', 'title', 'status', 'nature_of_claim', 'claimant', 'defendant', 'created_by', 'date_filed', 'hearing_date', 'created_at'];
        if (in_array($sortBy, $allowedSort, true)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->latest('created_at');
        }

        $cases = $query->paginate(15)->withQueryString();
        return view('case_management::cases.index', compact('cases', 'sortBy', 'sortDir'));
    }

    public function create(): View
    {
        $caseNumber = $this->caseService->generateCaseNumber();
        return view('case_management::cases.create', compact('caseNumber'));
    }

    public function store(StoreCaseRequest $request): RedirectResponse
    {
        $case = $this->caseService->create($request->validated());
        return redirect()->route('cases.show', $case)->with('success', 'Case registered successfully.');
    }

    public function show(CaseModel $case): View
    {
        $case->load(['createdByUser', 'documents.uploader', 'trashedDocuments.deletedByUser', 'notes.user']);
        return view('case_management::cases.show', compact('case'));
    }

    public function edit(CaseModel $case): View
    {
        return view('case_management::cases.edit', compact('case'));
    }

    public function update(UpdateCaseRequest $request, CaseModel $case): RedirectResponse
    {
        $this->caseService->update($case, $request->validated());
        return redirect()->route('cases.show', $case)->with('success', 'Case updated successfully.');
    }

    public function destroy(CaseModel $case): RedirectResponse
    {
        $deletedCaseNumber = $case->case_number;
        $old = $case->toArray();

        $case->delete();

        $this->auditService->log(
            action: 'case.deleted',
            auditableType: CaseModel::class,
            auditableId: $case->id,
            oldValues: $old,
            newValues: ['deleted' => true, 'case_number' => $deletedCaseNumber]
        );

        return redirect()->route('cases.index')->with('success', "Case {$deletedCaseNumber} deleted.");
    }
}
