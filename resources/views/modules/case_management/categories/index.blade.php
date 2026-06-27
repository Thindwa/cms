@extends('layouts.app')

@section('title', 'Categories')
@section('page-title', 'Categories')
@section('breadcrumbs', 'Case Management / Categories')

@section('actions')
    @can('cases.categories.create')
        <a href="{{ route('cases.categories.create') }}" class="btn btn-primary btn-sm">Add Category</a>
    @endcan
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Cases</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    <tr>
                        <td>{{ $category->name }}</td>
                        <td><code>{{ $category->slug }}</code></td>
                        <td>{{ $category->cases_count }}</td>
                        <td class="text-end">
                            @can('cases.categories.edit')
                                <a href="{{ route('cases.categories.edit', $category) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            @endcan
                            @can('cases.categories.delete')
                                <form method="POST" action="{{ route('cases.categories.destroy', $category) }}" class="d-inline"
                                      data-confirm-title="Delete Category"
                                      data-confirm-message="Delete category '{{ $category->name }}'?"
                                      data-confirm-button="Delete">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No categories yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
