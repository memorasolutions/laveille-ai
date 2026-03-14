<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Shortcodes'), 'subtitle' => __('Gestion')])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Shortcodes') }}</li>
    </ol>
</nav>

<div class="card">
    <div class="card-header border-bottom py-3 px-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-2">
                <i data-lucide="code-2" style="width:20px;height:20px;" class="text-primary"></i>
                <h5 class="mb-0 fw-semibold">{{ __('Shortcodes') }}</h5>
            </div>
            <div class="d-flex gap-2">
                <x-backoffice::help-modal id="helpShortcodesModal" :title="__('Shortcodes')" icon="code" :buttonLabel="__('Aide')">
                    @include('backoffice::themes.backend.shortcodes._help')
                </x-backoffice::help-modal>
                <a href="{{ route('admin.shortcodes.create') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2">
                    <i data-lucide="plus" style="width:16px;height:16px;"></i>
                    {{ __('Nouveau shortcode') }}
                </a>
            </div>
        </div>
    </div>
    <div class="card-body p-4">
        @livewire('shortcodes-table')
    </div>
</div>

@endsection
