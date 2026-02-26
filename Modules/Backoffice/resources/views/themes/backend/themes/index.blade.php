@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Thèmes', 'subtitle' => 'Sélection du thème du backoffice'])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active">{{ __('Thèmes') }}</li>
    </ol>
</nav>

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
            <h4 class="fw-bold mb-0">Sélection du thème</h4>
        </div>
    </div>
    <div class="card-body p-4">
        <p class="text-muted small mb-4">
            Choisissez le thème d'interface pour l'administration. Le thème actif est défini dans la configuration du backoffice.
            <br>
            Thème actif : <code class="badge bg-primary bg-opacity-10 text-primary font-monospace">{{ config('backoffice.theme', 'wowdash') }}</code>
        </p>

        <form method="POST" action="{{ route('admin.themes.switch') }}">
            @csrf

            <div class="row g-3" x-data="{ selected: '{{ config('backoffice.theme', 'wowdash') }}' }">

                {{-- WowDash --}}
                <div class="col-sm-6 col-lg-4">
                    <label class="cursor-pointer d-block h-100"
                           :class="selected === 'wowdash' ? 'border-primary border-2 rounded-3' : ''">
                        <input type="radio" name="theme" value="wowdash" class="visually-hidden" x-model="selected">
                        <div class="card border overflow-hidden h-100"
                             :class="selected === 'wowdash' ? 'border-primary border-2' : ''">
                            <div class="d-flex align-items-center justify-content-center"
                                 style="height:128px;background:linear-gradient(135deg,#EEF2FF,#DBEAFE);">
                                <i data-lucide="layout-dashboard" style="width:48px;height:48px;" class="text-primary opacity-50"></i>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="fw-semibold">WowDash</span>
                                    <span x-show="selected === 'wowdash'" class="badge bg-success bg-opacity-10 text-success">Actif</span>
                                </div>
                                <p class="text-muted small mb-0">Thème moderne avec sidebar colorée, Iconify icons et design Bootstrap avancé.</p>
                            </div>
                        </div>
                    </label>
                </div>

                {{-- Tabler --}}
                <div class="col-sm-6 col-lg-4">
                    <label class="cursor-pointer d-block h-100"
                           :class="selected === 'tabler' ? 'border-primary border-2 rounded-3' : ''">
                        <input type="radio" name="theme" value="tabler" class="visually-hidden" x-model="selected">
                        <div class="card border overflow-hidden h-100"
                             :class="selected === 'tabler' ? 'border-primary border-2' : ''">
                            <div class="d-flex align-items-center justify-content-center"
                                 style="height:128px;background:linear-gradient(135deg,#F0F9FF,#ECFEFF);">
                                <i data-lucide="table" style="width:48px;height:48px;" class="text-info opacity-50"></i>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="fw-semibold">Tabler</span>
                                    <span x-show="selected === 'tabler'" class="badge bg-success bg-opacity-10 text-success">Actif</span>
                                </div>
                                <p class="text-muted small mb-0">Thème épuré basé sur Tabler UI, Bootstrap 5 avec Tabler Icons.</p>
                            </div>
                        </div>
                    </label>
                </div>

                {{-- Backend (NobleUI) --}}
                <div class="col-sm-6 col-lg-4">
                    <label class="cursor-pointer d-block h-100"
                           :class="selected === 'backend' ? 'border-primary border-2 rounded-3' : ''">
                        <input type="radio" name="theme" value="backend" class="visually-hidden" x-model="selected">
                        <div class="card border overflow-hidden h-100"
                             :class="selected === 'backend' ? 'border-primary border-2' : ''">
                            <div class="d-flex align-items-center justify-content-center"
                                 style="height:128px;background:linear-gradient(135deg,#F5F3FF,#FAF5FF);">
                                <i data-lucide="monitor" style="width:48px;height:48px;color:#8B5CF6;" class="opacity-50"></i>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="fw-semibold">Backend (NobleUI)</span>
                                    <span x-show="selected === 'backend'" class="badge bg-success bg-opacity-10 text-success">Actif</span>
                                </div>
                                <p class="text-muted small mb-0">Thème NobleUI Bootstrap 5.3.8 avec dark sidebar, Lucide icons et design épuré.</p>
                            </div>
                        </div>
                    </label>
                </div>

            </div>

            <div class="d-flex align-items-center gap-3 mt-4">
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <i data-lucide="check" class="icon-sm"></i>
                    Appliquer le thème
                </button>
            </div>
        </form>
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
            Le thème peut aussi être changé directement dans le fichier de configuration :
        </p>
        <pre class="bg-light border rounded-3 p-3 small font-monospace"># config/backoffice.php
'theme' => env('BACKOFFICE_THEME', '{{ config('backoffice.theme', 'wowdash') }}'),

# .env
BACKOFFICE_THEME=backend</pre>
    </div>
</div>

@endsection
