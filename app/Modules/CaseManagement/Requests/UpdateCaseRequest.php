<?php

namespace App\Modules\CaseManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cases.edit');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'date_filed' => ['nullable', 'date'],
            'reference_number' => ['nullable', 'string', 'max:64'],
            'defendant' => ['nullable', 'string', 'max:255'],
            'nature_of_claim' => ['nullable', 'string', 'max:255'],
            'claimant' => ['nullable', 'string', 'max:255'],
            'cause_number' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string'],
        ];
    }
}
