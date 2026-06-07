@extends('layouts.app')

@section('title', 'Edit Case')
@section('page-title', 'Edit Case')
@section('breadcrumbs', 'Case Management / Edit')

@section('content')
<form action="{{ route('cases.update', $case) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">Case Details</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Serial Number</label>
                        <input type="text" class="form-control" value="{{ $case->case_number }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Case Title</label>
                        <input type="text" name="case_title" class="form-control @error('case_title') is-invalid @enderror" value="{{ old('case_title', $case->case_title) }}">
                        @error('case_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date Filed</label>
                        <input type="date" name="date_filed" class="form-control @error('date_filed') is-invalid @enderror" value="{{ old('date_filed', $case->date_filed?->format('Y-m-d')) }}">
                        @error('date_filed')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Upcoming Hearing Date</label>
                        <input type="date" name="hearing_date" class="form-control @error('hearing_date') is-invalid @enderror" value="{{ old('hearing_date', $case->hearing_date?->format('Y-m-d')) }}">
                        @error('hearing_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">AG Reference Number</label>
                        <input type="text" name="reference_number" class="form-control @error('reference_number') is-invalid @enderror" value="{{ old('reference_number', $case->reference_number) }}">
                        @error('reference_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Civil Case Number</label>
                        <input type="text" name="civil_case_number" class="form-control @error('civil_case_number') is-invalid @enderror" value="{{ old('civil_case_number', $case->civil_case_number) }}">
                        @error('civil_case_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Defendant</label>
                        <input type="text" name="defendant" class="form-control @error('defendant') is-invalid @enderror" value="{{ old('defendant', $case->defendant) }}">
                        @error('defendant')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nature of Claim</label>
                        <input type="text" name="nature_of_claim" class="form-control @error('nature_of_claim') is-invalid @enderror" value="{{ old('nature_of_claim', $case->nature_of_claim) }}">
                        @error('nature_of_claim')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Claimant</label>
                        <input type="text" name="claimant" class="form-control @error('claimant') is-invalid @enderror" value="{{ old('claimant', $case->claimant) }}">
                        @error('claimant')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cause Number</label>
                        <input type="text" name="cause_number" class="form-control @error('cause_number') is-invalid @enderror" value="{{ old('cause_number', $case->cause_number) }}">
                        @error('cause_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Officer Dealing <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $case->title) }}" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="open" @selected(old('status', $case->status) === 'open')>Open</option>
                            <option value="in_progress" @selected(old('status', $case->status) === 'in_progress')>In Progress</option>
                            <option value="closed" @selected(old('status', $case->status) === 'closed')>Closed</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Priority (1-10) <span class="text-danger">*</span></label>
                        <input type="number"
                               name="priority"
                               min="1"
                               max="10"
                               step="1"
                               class="form-control @error('priority') is-invalid @enderror"
                               value="{{ old('priority', (int) $case->priority) }}"
                               required>
                        @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Case Summary (Primary)</label>
                        <textarea id="description-editor" name="description" class="form-control @error('description') is-invalid @enderror" rows="8">{{ old('description', $case->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="{{ route('cases.show', $case) }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    tinymce.init({
        selector: '#description-editor',
        height: 280,
        menubar: false,
        branding: false,
        plugins: 'lists link table code wordcount',
        toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link table | removeformat | code',
        content_style: 'body { font-family: Segoe UI, Arial, sans-serif; font-size: 14px; }'
    });
});
</script>
@endpush
