<?php

namespace App\Modules\CaseManagement\Requests;

use App\Modules\CaseManagement\Models\CaseCategory;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cases.edit');
    }

    public function rules(): array
    {
        $rules = [
            'case_title' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'date_filed' => ['nullable', 'date'],
            'hearing_date' => ['nullable', 'date'],
            'reference_number' => ['nullable', 'string', 'max:64'],
            'defendant' => ['nullable', 'string', 'max:255'],
            'nature_of_claim' => ['nullable', 'string', 'max:255'],
            'claimant' => ['nullable', 'string', 'max:255'],
            'cause_number' => ['nullable', 'string', 'max:64'],
            'status' => ['required', 'string', 'in:active,dormant,closed'],
            'category_id' => ['nullable', 'string', 'uuid', 'exists:case_categories,id'],
            'description' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $plain = trim(strip_tags((string) $value));
                    if ($plain !== '' && preg_match('/[^\pL\pN\s\.\,\-\(\)\/\:\;\&\'\"\?\!\%\+\#]/u', $plain)) {
                        $fail('Invalid characters detected in the description.');
                    }
                },
            ],
        ];

        if ($this->filled('category_id')) {
            $category = CaseCategory::find($this->input('category_id'));
            if ($category && ! empty($category->required_fields)) {
                foreach ($category->required_fields as $field) {
                    if (isset($rules[$field]) && ! in_array('required', $rules[$field], true)) {
                        $rules[$field][] = 'required';
                    }
                }
            }
        }

        return $rules;
    }
}
