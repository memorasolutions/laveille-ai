<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => $title, 'subtitle' => $subtitle])

@section('content')

<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Emails envoyés') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="send" class="icon-md text-primary"></i>{{ __('Emails envoyés') }}</h4>
    <x-backoffice::help-modal id="helpMailLogModal" :title="__('Journal des emails')" icon="send" :buttonLabel="__('Aide')">
        @include('backoffice::themes.backend.mail-log._help')
    </x-backoffice::help-modal>
</div>

<div class="card">
    <div class="card-header py-3 px-4 border-bottom">
        <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
            <i data-lucide="send" class="text-primary icon-md"></i>
            {{ __('Emails envoyés') }}
        </h4>
    </div>
    <div class="card-body p-0">
        @livewire('backoffice-mail-log-table')
    </div>
</div>

@endsection
