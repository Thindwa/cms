<?php

namespace App\Modules\CaseManagement\Controllers;

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
        protected CaseManagementService $caseService
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

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = strtolower($request->get('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSort = ['case_number', 'reference_number', 'civil_case_number', 'title', 'nature_of_claim', 'claimant', 'defendant', 'created_by', 'date_filed', 'hearing_date', 'created_at'];
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
        $auditLogs = \App\Core\Audit\AuditLog::where('auditable_type', CaseModel::class)
            ->where('auditable_id', $case->id)
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
        return view('case_management::cases.show', compact('case', 'auditLogs'));
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
}
