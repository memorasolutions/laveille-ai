<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Utilisateurs'), 'subtitle' => __('Liste')])

@section('content')

<x-backoffice::driver-tour
    storage-key="driver_tour_users_{{ auth()->id() }}"
    :steps="[
        ['element' => '.page-content', 'popover' => ['title' => __('Gestion des utilisateurs'), 'description' => __('Gérez les comptes, rôles et accès de votre plateforme.'), 'side' => 'bottom']],
        ['element' => 'table.table', 'popover' => ['title' => __('Liste des utilisateurs'), 'description' => __('Tous les utilisateurs avec rôle, statut et actions.'), 'side' => 'bottom']],
        ['element' => 'a[href*=\"users/create\"]', 'popover' => ['title' => __('Nouvel utilisateur'), 'description' => __('Créez un compte utilisateur avec rôle et permissions.'), 'side' => 'left']],
    ]"
/>

<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Utilisateurs') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="users" class="icon-md text-primary"></i>{{ __('Utilisateurs') }}</h4>
    <div class="d-flex gap-2">
        <x-backoffice::help-modal id="helpUsersModal" :title="__('Utilisateurs')" icon="users" :buttonLabel="__('Aide')">
            @include('backoffice::themes.backend.users._help')
        </x-backoffice::help-modal>
        <a href="{{ route('admin.export.users') }}" class="btn btn-sm btn-success d-inline-flex align-items-center gap-2">
            <i data-lucide="download"></i>
            {{ __('Exporter CSV') }}
        </a>
        <a href="{{ route('admin.import.users') }}" class="btn btn-sm btn-info d-inline-flex align-items-center gap-2">
            <i data-lucide="upload"></i>
            {{ __('Importer CSV') }}
        </a>
        <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2">
            <i data-lucide="plus"></i>
            {{ __('Ajouter') }}
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @livewire('backoffice-users-table')
    </div>
</div>

@endsection
