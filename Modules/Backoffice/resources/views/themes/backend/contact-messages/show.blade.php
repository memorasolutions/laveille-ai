<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')
@section('title', __('Message de') . ' ' . $contactMessage->name)
@section('content')
<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.contact-messages.index') }}">{{ __('Messages') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $contactMessage->name }}</li>
    </ol>
</nav>
<div class="page-content">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0">{{ __('Message de contact') }}</h4>
        <a href="{{ route('admin.contact-messages.index') }}" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i> {{ __('Retour') }}
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0" style="text-transform: none">{{ $contactMessage->subject }}</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4" style="white-space: pre-wrap;">{{ $contactMessage->message }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('Informations') }}</h5>
                </div>
                <div class="card-body">
                    <dl class="mb-0">
                        <dt class="small text-muted">{{ __('Nom') }}</dt>
                        <dd>{{ $contactMessage->name }}</dd>

                        <dt class="small text-muted">{{ __('Email') }}</dt>
                        <dd>
                            <a href="mailto:{{ $contactMessage->email }}">{{ $contactMessage->email }}</a>
                        </dd>

                        <dt class="small text-muted">{{ __('Date') }}</dt>
                        <dd>{{ format_date($contactMessage->created_at, 'datetime') }}</dd>

                        <dt class="small text-muted">{{ __('Statut') }}</dt>
                        <dd>
                            @if($contactMessage->isNew())
                                <span class="badge bg-primary">{{ __('Non lu') }}</span>
                            @else
                                <span class="badge bg-secondary">{{ __('Lu') }}</span>
                                <small class="text-muted d-block">{{ $contactMessage->read_at?->diffForHumans() }}</small>
                            @endif
                        </dd>

                        @if($contactMessage->ip_address)
                        <dt class="small text-muted">{{ __('Adresse IP') }}</dt>
                        <dd class="text-muted small">{{ $contactMessage->ip_address }}</dd>
                        @endif
                    </dl>
                </div>
                <div class="card-footer">
                    {{-- Regroupement dans le composant DRY action-menu (2026-07-19). Corrige au
                         passage un bug de double confirmation : le bouton Supprimer déclenchait un
                         confirm() JS natif (interdit sur ce projet) PUIS, une fois confirmé,
                         soumettait le formulaire qui portait AUSSI un data-confirm="..." -
                         déclenchant une seconde confirmation (cette fois via la modale du thème) à
                         la suite de la première. Le composant action-menu gère la confirmation en
                         un seul passage via sa modale (event confirm-action), sans popup native. --}}
                    @include('core::components.action-menu', ['actions' => array_filter([
                        ['label' => __('Répondre par email'), 'icon' => 'reply', 'url' => 'mailto:' . $contactMessage->email . '?subject=Re: ' . urlencode($contactMessage->subject)],
                        auth()->user()?->can('delete_contacts') ? ['label' => __('Supprimer'), 'icon' => 'trash-2', 'url' => route('admin.contact-messages.destroy', $contactMessage), 'method' => 'DELETE', 'confirm' => __('Supprimer ce message ?'), 'danger' => true] : null,
                    ])])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
