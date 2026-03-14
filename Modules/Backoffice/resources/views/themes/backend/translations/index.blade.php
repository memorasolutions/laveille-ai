<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Traductions')])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Traductions') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="languages" class="icon-md text-primary"></i>{{ __('Traductions') }}</h4>
    <x-backoffice::help-modal id="helpTranslationsModal" :title="__('Traductions')" icon="languages" :buttonLabel="__('Aide')">
        @include('backoffice::themes.backend.translations._help')
    </x-backoffice::help-modal>
</div>

@livewire('backoffice-translations-manager')
@endsection
