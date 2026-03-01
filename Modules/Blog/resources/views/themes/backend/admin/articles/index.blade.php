<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Articles')])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.blog.articles.index') }}">{{ __('Blog') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Articles') }}</li>
    </ol>
</nav>

<div class="card">
    <div class="card-header py-3 px-4 border-bottom">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <h4 class="fw-bold mb-0">Articles</h4>
            <a href="{{ route('admin.blog.articles.create') }}"
               class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2">
                <i data-lucide="plus" class="icon-sm"></i>
                Nouvel article
            </a>
        </div>
    </div>
    <div class="card-body p-4">
        @livewire('backoffice-articles-table')
    </div>
</div>

@endsection
