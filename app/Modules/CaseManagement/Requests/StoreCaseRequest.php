<?php

namespace App\Modules\CaseManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cases.create');
    }

    public function rules(): array
    {
        return [
            'case_title' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'date_filed' => ['nullable', 'date'],
            'hearing_date' => ['nullable', 'date'],
            'reference_number' => ['nullable', 'string', 'max:64'],
            'civil_case_number' => ['nullable', 'string', 'max:64'],
            'defendant' => ['nullable', 'string', 'max:255'],
            'nature_of_claim' => ['nullable', 'string', 'max:255'],
            'claimant' => ['nullable', 'string', 'max:255'],
            'cause_number' => ['nullable', 'string', 'max:64'],
            'status' => ['required', 'string', 'in:open,in_progress,closed'],
            'priority' => ['required', 'integer', 'between:1,10'],
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
    }
}
