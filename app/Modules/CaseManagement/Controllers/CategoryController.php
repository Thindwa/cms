<?php

namespace App\Modules\CaseManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CaseManagement\Models\CaseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(CaseCategory::class, 'category');
    }

    public function index(): View
    {
        $categories = CaseCategory::query()
            ->withCount('cases')
            ->orderBy('name')
            ->get();

        return view('case_management::categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('case_management::categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:case_categories,name'],
            'required_fields' => ['nullable', 'array'],
            'required_fields.*' => ['string', 'in:claimant,defendant,reference_number,cause_number,nature_of_claim,date_filed,hearing_date'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['required_fields'] = $request->input('required_fields', []);

        CaseCategory::create($validated);

        return redirect()->route('cases.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(CaseCategory $category): View
    {
        return view('case_management::categories.edit', compact('category'));
    }

    public function update(Request $request, CaseCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:case_categories,name,' . $category->id],
            'required_fields' => ['nullable', 'array'],
            'required_fields.*' => ['string', 'in:claimant,defendant,reference_number,cause_number,nature_of_claim,date_filed,hearing_date'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['required_fields'] = $request->input('required_fields', []);

        $category->update($validated);

        return redirect()->route('cases.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(CaseCategory $category): RedirectResponse
    {
        if ($category->cases()->exists()) {
            return redirect()->route('cases.categories.index')
                ->with('error', 'Cannot delete category with associated cases. Reassign cases first.');
        }

        $category->delete();

        return redirect()->route('cases.categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
