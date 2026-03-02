@extends('layouts.app')

@section('title', 'Edit Case')
@section('page-title', 'Edit Case')
@section('breadcrumbs', 'Case Management / Edit')

@section('content')
<form action="{{ route('cases.update', $case) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="row">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">Case Details</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Serial Number</label>
                        <input type="text" class="form-control" value="{{ $case->case_number }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date Filed</label>
                        <input type="date" name="date_filed" class="form-control @error('date_filed') is-invalid @enderror" value="{{ old('date_filed', $case->date_filed?->format('Y-m-d')) }}">
                        @error('date_filed')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reference Number</label>
                        <input type="text" name="reference_number" class="form-control @error('reference_number') is-invalid @enderror" value="{{ old('reference_number', $case->reference_number) }}">
                        @error('reference_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                        <label class="form-label">Case Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $case->title) }}" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $case->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">Assignment & Status</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Officer Dealing</label>
                        <select name="assigned_to" class="form-select @error('assigned_to') is-invalid @enderror">
                            <option value="">— Unassigned —</option>
                            @foreach($officers as $o)
                                <option value="{{ $o->id }}" {{ old('assigned_to', $case->assigned_to) == $o->id ? 'selected' : '' }}>{{ $o->name }}</option>
                            @endforeach
                        </select>
                        @error('assigned_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-select">
                            <option value="low" {{ old('priority', $case->priority) === 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ old('priority', $case->priority) === 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ old('priority', $case->priority) === 'high' ? 'selected' : '' }}>High</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="open" {{ old('status', $case->status) === 'open' ? 'selected' : '' }}>Open</option>
                            <option value="in_progress" {{ old('status', $case->status) === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="closed" {{ old('status', $case->status) === 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
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
