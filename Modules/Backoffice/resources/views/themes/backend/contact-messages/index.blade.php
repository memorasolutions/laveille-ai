<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')
@section('title', __('Messages de contact'))
@section('content')
<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Messages de contact') }}</li>
    </ol>
</nav>
<div class="page-content">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
            <i data-lucide="mail" class="icon-md text-primary"></i>{{ __('Messages de contact') }}
        </h4>
        <x-backoffice::help-modal id="helpContactMessagesModal" :title="__('Messages de contact')" icon="mail" :buttonLabel="__('Aide')">
            @include('backoffice::themes.backend.contact-messages._help')
        </x-backoffice::help-modal>
    </div>

    @livewire('backoffice-contact-messages-table')
</div>
@endsection
