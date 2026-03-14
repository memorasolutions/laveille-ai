<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', __('Documentation'))

@section('content')
<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Tableau de bord') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Documentation') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="mb-0">
        <i data-lucide="book-open" class="me-2" aria-hidden="true"></i>
        {{ __('Documentation') }}
    </h4>
    <span class="badge bg-primary">{{ array_sum(array_map('count', $sections)) }} {{ __('rubriques') }}</span>
</div>

{{-- Table des matières --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">{{ __('Table des matières') }}</h5>
    </div>
    <div class="card-body">
        <div class="row">
            @foreach($sections as $category => $items)
            <div class="col-md-4 col-lg-3 mb-3">
                <h6 class="fw-bold text-primary mb-2">
                    <a href="#section-{{ Str::slug($category) }}" class="text-decoration-none">{{ $category }}</a>
                </h6>
                <ul class="list-unstyled small">
                    @foreach($items as $item)
                    <li class="mb-1">
                        <a href="#doc-{{ Str::slug($item['title']) }}" class="text-body text-decoration-none">
                            <i data-lucide="chevron-right" style="width:12px;height:12px;" aria-hidden="true"></i>
                            {{ $item['title'] }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Sections de documentation --}}
@foreach($sections as $category => $items)
<div class="mb-4" id="section-{{ Str::slug($category) }}">
    <h5 class="mb-3 d-flex align-items-center">
        <span class="badge bg-primary me-2">{{ count($items) }}</span>
        {{ $category }}
        <a href="#" class="ms-2 text-muted small" onclick="event.preventDefault();window.scrollTo({top:0,behavior:'smooth'})">
            <i data-lucide="arrow-up" style="width:14px;height:14px;" aria-hidden="true"></i>
        </a>
    </h5>

    <div class="accordion" id="accordion-{{ Str::slug($category) }}">
        @foreach($items as $index => $item)
        <div class="accordion-item" id="doc-{{ Str::slug($item['title']) }}">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapse-{{ Str::slug($category) }}-{{ $index }}"
                        aria-expanded="false"
                        aria-controls="collapse-{{ Str::slug($category) }}-{{ $index }}">
                    <i data-lucide="file-text" class="me-2" style="width:16px;height:16px;" aria-hidden="true"></i>
                    {{ $item['title'] }}
                </button>
            </h2>
            <div id="collapse-{{ Str::slug($category) }}-{{ $index }}"
                 class="accordion-collapse collapse"
                 data-bs-parent="#accordion-{{ Str::slug($category) }}">
                <div class="accordion-body">
                    {!! \Illuminate\Support\Facades\Blade::render(\Illuminate\Support\Facades\File::get($item['view_path'])) !!}
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endforeach
@endsection
