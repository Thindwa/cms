<?php

namespace App\Modules\CaseManagement\Controllers;

use App\Core\Audit\AuditLog;
use App\Core\Audit\AuditService;
use App\Http\Controllers\Controller;
use App\Modules\CaseManagement\Models\CaseCategory;
use App\Modules\CaseManagement\Models\CaseDocument;
use App\Modules\CaseManagement\Models\CaseModel;
use App\Modules\CaseManagement\Models\CaseNote;
use App\Modules\CaseManagement\Requests\StoreCaseRequest;
use App\Modules\CaseManagement\Requests\UpdateCaseRequest;
use App\Modules\CaseManagement\Services\ActivityService;
use App\Modules\CaseManagement\Services\CaseManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CaseController extends Controller
{
    public function __construct(
        protected CaseManagementService $caseService,
        protected AuditService $auditService,
        protected ActivityService $activityService,
    ) {
        $this->authorizeResource(CaseModel::class, 'case');
    }

    public function index(Request $request): View
    {
        $query = CaseModel::query()
            ->with(['createdByUser', 'category'])
            ->whereNotNull('case_number')
            ->where('case_number', '!=', '');

        if ($request->filled('cause_number')) {
            $query->where('cause_number', 'like', '%' . $request->cause_number . '%');
        }
        if ($request->filled('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }
        if ($request->filled('reference_number')) {
            $query->where('reference_number', 'like', '%' . $request->reference_number . '%');
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
            'cause_number', 'title', 'reference_number', 'party',
            'date_from', 'date_to', 'hearing_date_from', 'hearing_date_to',
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
        $allowedSort = ['case_number', 'case_title', 'reference_number', 'title', 'status', 'nature_of_claim', 'claimant', 'defendant', 'created_by', 'date_filed', 'hearing_date', 'created_at'];
        if (in_array($sortBy, $allowedSort, true)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->latest('created_at');
        }

        $cases = $query->paginate((int) config('app.items_per_page', 15))->withQueryString();
        $categories = CaseCategory::query()->orderBy('name')->get();
        return view('case_management::cases.index', compact('cases', 'sortBy', 'sortDir', 'categories'));
    }

    public function create(): View
    {
        $caseNumber = $this->caseService->generateCaseNumber();
        $categories = CaseCategory::query()->orderBy('name')->get();
        return view('case_management::cases.create', compact('caseNumber', 'categories'));
    }

    public function store(StoreCaseRequest $request): RedirectResponse
    {
        $case = $this->caseService->create($request->validated());
        $this->activityService->log($case, 'case.created', 'Case registered', [
            'case_number' => $case->case_number,
        ]);
        return redirect()->route('cases.show', $case)->with('success', 'Case registered successfully.');
    }

    public function show(CaseModel $case): View
    {
        $case->load(['category', 'createdByUser', 'assignedOfficer', 'documents.uploader', 'trashedDocuments.deletedByUser', 'notes.user', 'activities.user']);

        $officerChanges = AuditLog::query()
            ->where('auditable_type', CaseModel::class)
            ->where('auditable_id', $case->id)
            ->where('action', 'case.updated')
            ->with('actor')
            ->latest()
            ->limit(50)
            ->get()
            ->filter(fn (AuditLog $log) => (
                ($log->old_values['title'] ?? null) !== ($log->new_values['title'] ?? null)
            ));

        return view('case_management::cases.show', compact('case', 'officerChanges'));
    }

    public function edit(CaseModel $case): View
    {
        $categories = CaseCategory::query()->orderBy('name')->get();
        return view('case_management::cases.edit', compact('case', 'categories'));
    }

    public function update(UpdateCaseRequest $request, CaseModel $case): RedirectResponse
    {
        $changed = $case->getDirty();
        $this->caseService->update($case, $request->validated());
        $this->activityService->log($case, 'case.updated', 'Case details updated', [
            'changed' => array_keys($changed),
        ]);
        return redirect()->route('cases.show', $case)->with('success', 'Case updated successfully.');
    }

    public function updateOfficerDealing(Request $request, CaseModel $case): RedirectResponse
    {
        $this->authorize('update', $case);
        $validated = $request->validate(['title' => ['required', 'string', 'max:255']]);

        $old = $case->title;
        $case->update(['title' => $validated['title'], 'updated_by' => auth()->id()]);

        $this->auditService->log(
            action: 'case.updated',
            auditableType: CaseModel::class,
            auditableId: $case->id,
            oldValues: ['title' => $old],
            newValues: ['title' => $validated['title']],
        );
        $this->activityService->log($case, 'case.updated', "Officer dealing changed from \"{$old}\" to \"{$validated['title']}\"");

        return redirect()->route('cases.show', $case)->with('success', 'Officer dealing updated.');
    }

    public function bulkCategorize(Request $request): RedirectResponse
    {
        $request->validate([
            'case_ids' => ['required', 'array', 'min:1'],
            'case_ids.*' => ['required', 'string', 'uuid', 'exists:cases,id'],
            'category_id' => ['nullable', 'string', 'uuid', 'exists:case_categories,id'],
        ]);

        CaseModel::whereIn('id', $request->case_ids)->update([
            'category_id' => $request->category_id,
            'updated_by' => auth()->id(),
        ]);

        $count = count($request->case_ids);
        $label = $request->category_id
            ? CaseCategory::find($request->category_id)?->name ?? 'selected category'
            : 'Uncategorized';
        $updatedCases = CaseModel::whereIn('id', $request->case_ids)->get();
        foreach ($updatedCases as $c) {
            $this->activityService->log($c, 'case.categorized', "Categorized as \"{$label}\"");
        }

        return redirect()->back()->with('success', "{$count} case(s) categorized as \"{$label}\".");
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', CaseModel::class);

        $query = CaseModel::query()
            ->with('category')
            ->whereNotNull('case_number')
            ->where('case_number', '!=', '')
            ->orderBy('created_at', 'desc')
            ->limit(5000);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $cases = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="cases-export-' . now()->format('Y-m-d-His') . '.csv"',
        ];

        return response()->streamDownload(function () use ($cases) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Case No', 'Case Title', 'Date Filed', 'Hearing Date',
                'AG Reference', 'Cause No', 'Claimant', 'Defendant',
                'Status', 'Category', 'Officer Dealing', 'Assigned To', 'Created At',
            ]);

            foreach ($cases as $case) {
                fputcsv($handle, [
                    $case->case_number,
                    $case->case_title ?? '',
                    $case->date_filed?->format('Y-m-d') ?? '',
                    $case->hearing_date?->format('Y-m-d') ?? '',
                    $case->reference_number ?? '',
                    $case->cause_number ?? '',
                    $case->claimant ?? '',
                    $case->defendant ?? '',
                    $case->status ?? '',
                    $case->category?->name ?? '',
                    $case->title ?? '',
                    $case->assignedOfficer?->name ?? '',
                    $case->created_at?->format('Y-m-d H:i') ?? '',
                ]);
            }

            fclose($handle);
        }, 'cases-export.csv', $headers);
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
        $this->activityService->log($case, 'case.deleted', "Case {$deletedCaseNumber} deleted");

        return redirect()->route('cases.index')->with('success', "Case {$deletedCaseNumber} deleted.");
    }
}
