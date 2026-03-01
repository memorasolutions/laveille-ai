<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('SEO')])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('SEO - Meta tags') }}</li>
    </ol>
</nav>

<div class="card">
    <div class="card-header border-bottom py-3 px-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="search" class="icon-md text-primary"></i>SEO - Meta tags</h4>
            <a href="{{ route('admin.seo.create') }}"
               class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2">
                <i data-lucide="plus" style="width:16px;height:16px;"></i>
                Ajouter
            </a>
        </div>
    </div>
    <div class="card-body p-4">
        @livewire('backoffice-meta-tags-table')
    </div>
</div>

@endsection
