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
            @if($unreadCount > 0)
                <span class="badge bg-danger ms-2">{{ $unreadCount }} {{ $unreadCount > 1 ? __('non lus') : __('non lu') }}</span>
            @endif
        </h4>
        <x-backoffice::help-modal id="helpContactMessagesModal" :title="__('Messages de contact')" icon="mail" :buttonLabel="__('Aide')">
            @include('backoffice::themes.backend.contact-messages._help')
        </x-backoffice::help-modal>
    </div>

    {{-- Filtres --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="d-flex gap-3 align-items-center flex-wrap">
                <div class="d-flex align-items-center gap-2">
                    <label class="form-label mb-0 small text-nowrap">{{ __('Statut :') }}</label>
                    <select name="status" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                        <option value="">{{ __('Tous') }}</option>
                        <option value="new" {{ request('status') === 'new' ? 'selected' : '' }}>{{ __('Non lus') }}</option>
                        <option value="read" {{ request('status') === 'read' ? 'selected' : '' }}>{{ __('Lus') }}</option>
                    </select>
                </div>
                <div class="d-flex align-items-center gap-2 flex-grow-1">
                    <label class="form-label mb-0 small text-nowrap">{{ __('Recherche :') }}</label>
                    <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="{{ __('Nom, email ou sujet...') }}">
                </div>
                <button type="submit" class="btn btn-sm btn-outline-primary">
                    <i data-lucide="search"></i>
                </button>
                @if(request()->hasAny(['status', 'search']))
                <a href="{{ route('admin.contact-messages.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i data-lucide="x"></i> {{ __('Réinitialiser') }}
                </a>
                @endif
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if($messages->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width:30px"></th>
                            <th>{{ __('De') }}</th>
                            <th>{{ __('Sujet') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($messages as $msg)
                        <tr class="{{ $msg->isNew() ? 'fw-bold' : '' }}">
                            <td>
                                @if($msg->isNew())
                                    <span class="badge bg-primary rounded-circle p-1">&nbsp;</span>
                                @endif
                            </td>
                            <td>
                                <div>{{ $msg->name }}</div>
                                <small class="text-muted">{{ $msg->email }}</small>
                            </td>
                            <td>{{ Str::limit($msg->subject, 60) }}</td>
                            <td>
                                <span title="{{ $msg->created_at->format('d/m/Y H:i') }}">
                                    {{ $msg->created_at->diffForHumans() }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.contact-messages.show', $msg) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Voir') }}">
                                    <i data-lucide="eye"></i>
                                </a>
                                <form action="{{ route('admin.contact-messages.destroy', $msg) }}" method="POST" class="d-inline" data-confirm="{{ __('Supprimer ce message ?') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-sm btn-outline-danger" title="{{ __('Supprimer') }}" onclick="if(confirm('{{ __('Supprimer ce message ?') }}')) this.closest('form').submit()">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $messages->withQueryString()->links() }}
            </div>
            @else
            <div class="text-center py-5">
                <i data-lucide="mail" class="icon-xl text-muted mb-3"></i>
                <h5 class="text-muted">{{ __('Aucun message') }}</h5>
                <p class="text-muted">{{ __('Les messages envoyés via le formulaire de contact apparaîtront ici.') }}</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
