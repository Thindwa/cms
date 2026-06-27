@extends('layouts.app')

@section('title', 'Add Category')
@section('page-title', 'Add Category')
@section('breadcrumbs', 'Case Management / Categories / Add')

@section('content')
<form action="{{ route('cases.categories.store') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">Category Details</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Required Fields</label>
                        <div class="small text-muted mb-1">Fields that must be filled when this category is selected.</div>
                        @php $selected = old('required_fields', []); @endphp
                        @foreach(['claimant', 'defendant', 'reference_number', 'cause_number', 'nature_of_claim', 'date_filed', 'hearing_date'] as $field)
                            <div class="form-check">
                                <input type="checkbox" name="required_fields[]" value="{{ $field }}" class="form-check-input" id="rf_{{ $field }}" @checked(in_array($field, $selected))>
                                <label class="form-check-label" for="rf_{{ $field }}">{{ ucwords(str_replace('_', ' ', $field)) }}</label>
                            </div>
                        @endforeach
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <a href="{{ route('cases.categories.index') }}" class="btn btn-link">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
