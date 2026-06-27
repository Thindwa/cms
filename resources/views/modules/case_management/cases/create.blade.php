@extends('layouts.app')

@section('title', 'Register Case')
@section('page-title', 'Register New Case')
@section('breadcrumbs', 'Case Management / Register Case')

@section('content')
<form action="{{ route('cases.store') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">Case Details</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Serial Number</label>
                        <input type="text" class="form-control" value="{{ $caseNumber }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Case Title</label>
                        <input type="text" name="case_title" class="form-control @error('case_title') is-invalid @enderror" value="{{ old('case_title') }}">
                        @error('case_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date Filed</label>
                        <input type="date" name="date_filed" class="form-control @error('date_filed') is-invalid @enderror" value="{{ old('date_filed') }}">
                        @error('date_filed')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Upcoming Hearing Date</label>
                        <input type="date" name="hearing_date" class="form-control @error('hearing_date') is-invalid @enderror" value="{{ old('hearing_date') }}">
                        @error('hearing_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">AG Reference Number</label>
                        <input type="text" name="reference_number" class="form-control @error('reference_number') is-invalid @enderror" value="{{ old('reference_number') }}">
                        @error('reference_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Defendant</label>
                        <input type="text" name="defendant" class="form-control @error('defendant') is-invalid @enderror" value="{{ old('defendant') }}">
                        @error('defendant')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" id="categorySelect" class="form-select @error('category_id') is-invalid @enderror">
                            <option value="">— Select Category —</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" data-required="{{ json_encode($cat->required_fields ?? []) }}" @selected(old('category_id') === $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <div id="requiredFieldsInfo" class="small text-muted mt-1 d-none">
                            <i class="bi bi-info-circle"></i> Required for this category: <span id="requiredFieldsList"></span>
                        </div>
                        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nature of Claim</label>
                        <input type="text" name="nature_of_claim" class="form-control @error('nature_of_claim') is-invalid @enderror" value="{{ old('nature_of_claim') }}">
                        @error('nature_of_claim')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Claimant</label>
                        <input type="text" name="claimant" class="form-control @error('claimant') is-invalid @enderror" value="{{ old('claimant') }}">
                        @error('claimant')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cause Number</label>
                        <input type="text" name="cause_number" class="form-control @error('cause_number') is-invalid @enderror" value="{{ old('cause_number') }}">
                        @error('cause_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Officer Dealing <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                            <option value="dormant" @selected(old('status') === 'dormant')>Dormant</option>
                            <option value="closed" @selected(old('status') === 'closed')>Closed</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Case Summary (Primary)</label>
                        <textarea id="description-editor" name="description" class="form-control @error('description') is-invalid @enderror" rows="8">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="{{ route('cases.index') }}" class="btn btn-outline-secondary">Cancel</a>
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

    const categorySelect = document.getElementById('categorySelect');
    const requiredInfo = document.getElementById('requiredFieldsInfo');
    const requiredList = document.getElementById('requiredFieldsList');
    const fieldLabels = { claimant: 'Claimant', defendant: 'Defendant', reference_number: 'AG Ref', cause_number: 'Cause No', nature_of_claim: 'Nature of Claim', date_filed: 'Date Filed', hearing_date: 'Hearing Date' };

    function updateRequiredFields() {
        const selected = categorySelect.options[categorySelect.selectedIndex];
        const fields = selected ? JSON.parse(selected.dataset.required || '[]') : [];
        if (fields.length > 0) {
            requiredList.textContent = fields.map(f => fieldLabels[f] || f).join(', ');
            requiredInfo.classList.remove('d-none');
        } else {
            requiredInfo.classList.add('d-none');
        }
    }

    categorySelect.addEventListener('change', updateRequiredFields);
    updateRequiredFields();
});
</script>
@endpush
