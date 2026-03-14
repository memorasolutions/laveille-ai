<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::layouts.admin', ['title' => 'Dupe Models', 'subtitle' => 'Modifier'])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('core.dupe-models.index') }}">{{ __('Dupe Models') }}</a></li>
        <li class="breadcrumb-item active">{{ __('Modifier') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="pencil" class="icon-md text-primary"></i>{{ __('Modifier') }} #{{ $dupeModel->id }}</h4>
    <a href="{{ route('core.dupe-models.index') }}" class="btn btn-sm btn-light d-inline-flex align-items-center gap-2">
        <i data-lucide="arrow-left"></i> {{ __('Retour') }}
    </a>
</div>

<div class="card">
    <div class="card-header border-bottom py-3 px-4">
        <h5 class="fw-semibold mb-0">{{ __('Informations') }}</h5>
    </div>
    <div class="card-body p-4">
        <form action="{{ route('core.dupe-models.update', $dupeModel->id) }}" method="POST">
            @csrf
            @method('PUT')
            @include('core::dupe-models._fields', ['dupeModel' => $dupeModel])
            <div class="d-flex align-items-center gap-3 mt-3">
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <i data-lucide="save" class="icon-sm"></i> {{ __('Enregistrer') }}
                </button>
                <a href="{{ route('core.dupe-models.index') }}" class="btn btn-light d-inline-flex align-items-center gap-2">
                    <i data-lucide="x" class="icon-sm"></i> {{ __('Annuler') }}
                </a>
            </div>
        </form>
    </div>
</div>

@endsection