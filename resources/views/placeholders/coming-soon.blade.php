@extends('layouts.app')

@section('title', $moduleLabel . ' — Coming soon')
@section('breadcrumbs', $moduleLabel)

@section('content')
<div class="row justify-content-center py-5">
    <div class="col-md-8 col-lg-6 text-center">
        <div class="mb-4 opacity-50">
            <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="currentColor" class="text-secondary" viewBox="0 0 16 16">
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
            </svg>
        </div>
        <h2 class="h4 mb-2">{{ $moduleLabel }}</h2>
        <p class="text-muted mb-4">This module is planned for a future release. Check back later.</p>
        <a href="{{ route('dashboard') }}" class="btn btn-primary">Back to Dashboard</a>
    </div>
</div>
@endsection
