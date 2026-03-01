<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::layouts.admin', ['title' => 'Catégories', 'subtitle' => 'Blog'])

@section('content')
<div class="card h-100 p-0">
    <div class="card-header border-bottom py-3 px-4 d-flex align-items-center flex-wrap gap-3 justify-content-end">
        <a href="{{ route('admin.blog.categories.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-2">
            <i data-lucide="plus"></i>
            Nouvelle catégorie
        </a>
    </div>
    <div class="card-body p-4">
        @livewire('backoffice-categories-table')
    </div>
</div>
@endsection
