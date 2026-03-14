<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => $title, 'subtitle' => $subtitle])

@section('content')

<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Cache') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="database" class="icon-md text-primary"></i>{{ __('Cache') }}</h4>
    <x-backoffice::help-modal id="helpCacheModal" :title="__('Cache applicatif')" icon="database" :buttonLabel="__('Aide')">
        @include('backoffice::themes.backend.cache._help')
    </x-backoffice::help-modal>
</div>

@if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
        <i data-lucide="check-circle" style="width:16px;height:16px;flex-shrink:0;"></i>
        {{ session('success') }}
    </div>
@endif

<div class="row g-4 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body p-4 text-center">
                <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 mx-auto mb-3"
                     style="width:56px;height:56px;">
                    <i data-lucide="database" style="width:28px;height:28px;" class="text-primary"></i>
                </div>
                <h6 class="fw-semibold mb-1">{{ __('Cache applicatif') }}</h6>
                <p class="text-muted small mb-4">{{ __('Données mises en cache (Redis/file)') }}</p>
                <form action="{{ route('admin.cache.clear-cache') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                        {{ __('Vider') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body p-4 text-center">
                <div class="d-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-10 mx-auto mb-3"
                     style="width:56px;height:56px;">
                    <i data-lucide="settings" style="width:28px;height:28px;" class="text-warning"></i>
                </div>
                <h6 class="fw-semibold mb-1">{{ __('Configuration') }}</h6>
                <p class="text-muted small mb-4">{{ __('Cache des fichiers config/') }}</p>
                <form action="{{ route('admin.cache.clear-config') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning btn-sm w-100">
                        {{ __('Vider') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body p-4 text-center">
                <div class="d-flex align-items-center justify-content-center rounded-circle bg-info bg-opacity-10 mx-auto mb-3"
                     style="width:56px;height:56px;">
                    <i data-lucide="eye" style="width:28px;height:28px;" class="text-info"></i>
                </div>
                <h6 class="fw-semibold mb-1">{{ __('Vues compilées') }}</h6>
                <p class="text-muted small mb-4">{{ __('Cache des templates Blade') }}</p>
                <form action="{{ route('admin.cache.clear-views') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-info btn-sm w-100">
                        {{ __('Vider') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body p-4 text-center">
                <div class="d-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 mx-auto mb-3"
                     style="width:56px;height:56px;">
                    <i data-lucide="map" style="width:28px;height:28px;" class="text-success"></i>
                </div>
                <h6 class="fw-semibold mb-1">{{ __('Routes') }}</h6>
                <p class="text-muted small mb-4">{{ __('Cache du registre de routes') }}</p>
                <form action="{{ route('admin.cache.clear-routes') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-success btn-sm w-100">
                        {{ __('Vider') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-4 text-center">
        <form action="{{ route('admin.cache.clear-all') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-danger d-inline-flex align-items-center gap-2">
                <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
                {{ __('Vider tous les caches') }}
            </button>
        </form>
    </div>
</div>

@endsection
