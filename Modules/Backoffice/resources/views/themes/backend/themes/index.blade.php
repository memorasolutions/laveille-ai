<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Thèmes'), 'subtitle' => __('Sélection du thème du backoffice')])

@section('content')

<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active">{{ __('Thèmes') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="palette" class="icon-md text-primary"></i>{{ __('Thèmes') }}</h4>
    <x-backoffice::help-modal id="helpThemesModal" :title="__('Thèmes')" icon="palette" :buttonLabel="__('Aide')">
        @include('backoffice::themes.backend.themes._help')
    </x-backoffice::help-modal>
</div>

@if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3" role="alert">
        <i data-lucide="check-circle" class="icon-sm flex-shrink-0"></i>
        {{ session('success') }}
    </div>
@endif

<div class="card mb-4">
    <div class="card-header py-3 px-4 border-bottom">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="palette" class="icon-md text-primary"></i>
            <h4 class="fw-bold mb-0">{{ __('Sélection du thème') }}</h4>
        </div>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-sm-6 col-lg-4">
                <div class="card border border-primary border-2 overflow-hidden h-100">
                    <div class="d-flex align-items-center justify-content-center"
                         style="height:128px;background:linear-gradient(135deg,#F5F3FF,#FAF5FF);">
                        <i data-lucide="monitor" style="width:48px;height:48px;color:#8B5CF6;" class="opacity-50"></i>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="fw-semibold">Backend (NobleUI)</span>
                            <span class="badge bg-success bg-opacity-10 text-success">{{ __('Actif') }}</span>
                        </div>
                        <p class="text-muted small mb-0">{{ __('Thème NobleUI Bootstrap 5.3.8 avec dark sidebar, Lucide icons et design épuré.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Info technique --}}
<div class="card">
    <div class="card-header py-3 px-4 border-bottom">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="info" class="icon-md text-muted"></i>
            <h4 class="fw-semibold mb-0">Configuration</h4>
        </div>
    </div>
    <div class="card-body p-4">
        <p class="text-muted mb-3">
            {{ __('Le thème peut aussi être changé directement dans le fichier de configuration :') }}
        </p>
        <pre class="bg-light border rounded-3 p-3 small font-monospace"># config/backoffice.php
'theme' => env('BACKOFFICE_THEME', '{{ config('backoffice.theme', 'backend') }}'),

# .env
BACKOFFICE_THEME=backend</pre>
    </div>
</div>

@endsection
