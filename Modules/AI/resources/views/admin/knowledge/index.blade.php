<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')
@section('title', __('Base de connaissances IA'))
@section('content')
<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Tableau de bord') }}</a></li>
        <li class="breadcrumb-item"><span>{{ __('IA') }}</span></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Base de connaissances') }}</li>
    </ol>
</nav>
<div class="page-content">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
            <i data-lucide="brain" class="icon-md text-primary"></i>
            {{ __('Base de connaissances') }}
            <span class="badge bg-secondary fw-normal fs-6">{{ $documents->total() }}</span>
        </h4>
        <div class="d-flex gap-2">
            <x-backoffice::help-modal id="helpKnowledgeModal" :title="__('Base de connaissances IA')" icon="brain" :buttonLabel="__('Aide')">
                @include('ai::admin.knowledge._help')
            </x-backoffice::help-modal>
            <a href="{{ route('admin.ai.knowledge.create') }}" class="btn btn-primary">
                <i data-lucide="plus"></i> {{ __('Ajouter un document') }}
            </a>
            <a href="{{ route('admin.ai.knowledge.index') }}" class="btn btn-outline-secondary" title="{{ __('Synchroniser les sources (FAQ, Pages, Articles)') }}">
                <i data-lucide="refresh-cw"></i> {{ __('Synchroniser') }}
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i data-lucide="check-circle" class="me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Fermer') }}"></button>
    </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.ai.knowledge.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="source_type" class="form-label">{{ __('Type de source') }}</label>
                    <select name="source_type" id="source_type" class="form-select" onchange="this.form.submit()">
                        <option value="">{{ __('Tous les types') }}</option>
                        <option value="manual"  @selected(request('source_type') === 'manual')>{{ __('Manuel') }}</option>
                        <option value="faq"     @selected(request('source_type') === 'faq')>{{ __('FAQ') }}</option>
                        <option value="page"    @selected(request('source_type') === 'page')>{{ __('Page') }}</option>
                        <option value="article" @selected(request('source_type') === 'article')>{{ __('Article') }}</option>
                        <option value="service" @selected(request('source_type') === 'service')>{{ __('Service') }}</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="q" class="form-label">{{ __('Rechercher') }}</label>
                    <input type="text" name="q" id="q" class="form-control" placeholder="{{ __('Titre du document...') }}" value="{{ request('q') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i data-lucide="search"></i> {{ __('Filtrer') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            @if($documents->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Titre') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th class="text-center">{{ __('Chunks') }}</th>
                            <th>{{ __('Statut') }}</th>
                            <th>{{ __('Dernière sync') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($documents as $document)
                        @php
                            $typeBadge = match($document->source_type) {
                                'manual'  => 'bg-primary',
                                'faq'     => 'bg-info',
                                'page'    => 'bg-success',
                                'article' => 'bg-warning text-dark',
                                'service' => 'bg-secondary',
                                default   => 'bg-secondary',
                            };
                            $typeLabel = match($document->source_type) {
                                'manual'  => __('Manuel'),
                                'faq'     => __('FAQ'),
                                'page'    => __('Page'),
                                'article' => __('Article'),
                                'service' => __('Service'),
                                default   => $document->source_type,
                            };
                        @endphp
                        <tr>
                            <td class="align-middle fw-medium">{{ $document->title }}</td>
                            <td class="align-middle">
                                <span class="badge {{ $typeBadge }}">{{ $typeLabel }}</span>
                            </td>
                            <td class="align-middle text-center">
                                <span class="badge bg-light text-dark border">{{ $document->chunks_count }}</span>
                            </td>
                            <td class="align-middle">
                                @if($document->is_active)
                                    <span class="badge bg-success">{{ __('Actif') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('Inactif') }}</span>
                                @endif
                            </td>
                            <td class="align-middle text-muted small">
                                @if($document->last_synced_at)
                                    {{ $document->last_synced_at->diffForHumans() }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="align-middle text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="{{ route('admin.ai.knowledge.edit', $document) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Modifier') }}">
                                        <i data-lucide="edit"></i>
                                    </a>
                                    <form action="{{ route('admin.ai.knowledge.destroy', $document) }}" method="POST" class="d-inline" x-data>
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-outline-danger" title="{{ __('Supprimer') }}"
                                                @click="$dispatch('confirm-action', { title: @js(__('Confirmer')), message: @js(__('Supprimer ce document de la base de connaissances ?')), action: () => $el.closest('form').submit() })">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($documents->hasPages())
            <div class="card-footer d-flex justify-content-center">
                {{ $documents->links() }}
            </div>
            @endif
            @else
            <div class="text-center py-5">
                <i data-lucide="brain" class="icon-xl text-muted mb-3"></i>
                <h5 class="text-muted">{{ __('Aucun document dans la base de connaissances') }}</h5>
                <p class="text-muted mb-4">{{ __('Ajoutez des documents pour alimenter le chatbot IA.') }}</p>
                <a href="{{ route('admin.ai.knowledge.create') }}" class="btn btn-primary">
                    <i data-lucide="plus"></i> {{ __('Ajouter un document') }}
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
