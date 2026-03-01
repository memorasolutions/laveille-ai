@extends('backoffice::layouts.admin', ['title' => 'Articles', 'subtitle' => 'Blog'])

@section('content')

<div class="card h-100 p-0 rounded-3">
    <div class="card-header border-bottom bg-body py-3 px-4 d-flex align-items-center flex-wrap gap-3 justify-content-end">
        <a href="{{ route('admin.blog.articles.create') }}" class="btn btn-primary btn-sm px-2 py-2 rounded-2 d-flex align-items-center gap-2">
            <i data-lucide="plus"></i>
            Nouvel article
        </a>
    </div>
    <div class="card-body px-4 py-4">
        @livewire('backoffice-articles-table')
    </div>
</div>

@endsection
