<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')
@section('title', __('Sources URL - Base de connaissances'))
@section('content')
<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Tableau de bord') }}</a></li>
        <li class="breadcrumb-item"><span>{{ __('IA') }}</span></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.ai.knowledge.index') }}">{{ __('Base de connaissances') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Sources URL') }}</li>
    </ol>
</nav>
<div class="page-content">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
            <i data-lucide="link" class="icon-md text-primary"></i>
            {{ __('Sources URL') }}
            <span class="badge bg-secondary fw-normal fs-6">{{ $urls->total() }}</span>
        </h4>
        <div class="d-flex gap-2">
            <x-backoffice::help-modal id="helpUrlsModal" :title="__('Sources URL')" icon="link" :buttonLabel="__('Aide')">
                @include('ai::admin.urls._help')
            </x-backoffice::help-modal>
            <a href="{{ route('admin.ai.urls.create') }}" class="btn btn-primary">
                <i data-lucide="plus"></i> {{ __('Ajouter une URL') }}
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i data-lucide="check-circle" class="me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Fermer') }}"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i data-lucide="alert-circle" class="me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Fermer') }}"></button>
    </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.ai.urls.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="scrape_status" class="form-label">{{ __('Statut') }}</label>
                    <select name="scrape_status" id="scrape_status" class="form-select" onchange="this.form.submit()">
                        <option value="">{{ __('Tous') }}</option>
                        <option value="pending"        @selected(request('scrape_status') === 'pending')>{{ __('En attente') }}</option>
                        <option value="scraping"       @selected(request('scrape_status') === 'scraping')>{{ __('En cours') }}</option>
                        <option value="completed"      @selected(request('scrape_status') === 'completed')>{{ __('Terminé') }}</option>
                        <option value="failed"         @selected(request('scrape_status') === 'failed')>{{ __('Échec') }}</option>
                        <option value="robots_blocked" @selected(request('scrape_status') === 'robots_blocked')>{{ __('Robots bloqué') }}</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="q" class="form-label">{{ __('Rechercher') }}</label>
                    <input type="text" name="q" id="q" class="form-control"
                           placeholder="{{ __('Label ou URL...') }}"
                           value="{{ request('q') }}">
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
            @if($urls->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Label') }}</th>
                            <th>{{ __('URL') }}</th>
                            <th class="text-center">{{ __('Robots') }}</th>
                            <th class="text-center">{{ __('Pages indexées') }}</th>
                            <th>{{ __('Statut') }}</th>
                            <th>{{ __('Fréquence') }}</th>
                            <th>{{ __('Dernière sync') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($urls as $url)
                        @php
                            $statusBadge = match($url->scrape_status) {
                                'pending'        => 'bg-secondary',
                                'scraping'       => 'bg-info',
                                'completed'      => 'bg-success',
                                'failed'         => 'bg-danger',
                                'robots_blocked' => 'bg-warning text-dark',
                                default          => 'bg-secondary',
                            };
                            $statusLabel = match($url->scrape_status) {
                                'pending'        => __('En attente'),
                                'scraping'       => __('En cours'),
                                'completed'      => __('Terminé'),
                                'failed'         => __('Échec'),
                                'robots_blocked' => __('Robots bloqué'),
                                default          => $url->scrape_status,
                            };
                            $freqLabel = match($url->scrape_frequency) {
                                'manual'  => __('Manuel'),
                                'daily'   => __('Quotidien'),
                                'weekly'  => __('Hebdomadaire'),
                                'monthly' => __('Mensuel'),
                                default   => $url->scrape_frequency,
                            };
                        @endphp
                        <tr>
                            <td class="align-middle fw-medium">{{ $url->label }}</td>
                            <td class="align-middle text-muted small">
                                <span title="{{ $url->url }}">{{ Str::limit($url->url, 40) }}</span>
                            </td>
                            <td class="align-middle text-center">
                                @if($url->robots_allowed)
                                    <i data-lucide="check-circle" class="text-success" title="{{ __('Autorisé') }}"></i>
                                @else
                                    <i data-lucide="x-circle" class="text-danger" title="{{ __('Bloqué') }}"></i>
                                @endif
                            </td>
                            <td class="align-middle text-center">
                                <span class="badge bg-light text-dark border">{{ $url->documents_count }}</span>
                            </td>
                            <td class="align-middle">
                                <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="align-middle text-muted small">{{ $freqLabel }}</td>
                            <td class="align-middle text-muted small">
                                @if($url->last_scraped_at)
                                    {{ $url->last_scraped_at->diffForHumans() }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="align-middle text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <form action="{{ route('admin.ai.urls.scrape', $url) }}" method="POST" class="d-inline" x-data>
                                        @csrf
                                        <button type="button" class="btn btn-sm btn-outline-secondary" title="{{ __('Lancer le scraping') }}"
                                                @click="$dispatch('confirm-action', { title: @js(__('Confirmer')), message: @js(__('Lancer le scraping de cette URL ?')), action: () => $el.closest('form').submit() })">
                                            <i data-lucide="refresh-cw"></i>
                                        </button>
                                    </form>
                                    <a href="{{ route('admin.ai.urls.edit', $url) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Modifier') }}">
                                        <i data-lucide="edit"></i>
                                    </a>
                                    <form action="{{ route('admin.ai.urls.destroy', $url) }}" method="POST" class="d-inline" x-data>
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-outline-danger" title="{{ __('Supprimer') }}"
                                                @click="$dispatch('confirm-action', { title: @js(__('Confirmer')), message: @js(__('Supprimer cette source URL et tous ses documents indexés ?')), action: () => $el.closest('form').submit() })">
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
            @if($urls->hasPages())
            <div class="card-footer d-flex justify-content-center">
                {{ $urls->links() }}
            </div>
            @endif
            @else
            <div class="text-center py-5">
                <i data-lucide="link" class="icon-xl text-muted mb-3"></i>
                <h5 class="text-muted">{{ __('Aucune source URL configurée') }}</h5>
                <p class="text-muted mb-4">{{ __('Ajoutez des URLs pour que le chatbot IA puisse indexer leur contenu.') }}</p>
                <a href="{{ route('admin.ai.urls.create') }}" class="btn btn-primary">
                    <i data-lucide="plus"></i> {{ __('Ajouter une URL') }}
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
