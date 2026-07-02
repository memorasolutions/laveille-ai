<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Liens courts', 'subtitle' => 'Gestion des URLs raccourcies'])

@section('breadcrumbs')
<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item active" aria-current="page">Liens courts</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold d-flex align-items-center gap-2"><i data-lucide="link" class="icon-md text-primary"></i>{{ __('Liens courts') }}</h4>
    <div class="d-flex align-items-center gap-2">
        <x-backoffice::help-modal id="helpShortUrlModal" :title="__('Liens courts – comment ça marche ?')" icon="link" :buttonLabel="__('Aide')">
            @include('shorturl::admin._help')
        </x-backoffice::help-modal>
        <a href="{{ route('admin.short-urls.create') }}" class="btn btn-primary">
            <i data-lucide="plus" style="width:16px;height:16px;" class="me-1"></i>
            {{ __('Créer un lien') }}
        </a>
    </div>
</div>

{{-- Widget statistiques --}}
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 text-muted small mb-1">
                    <i data-lucide="link" style="width:16px;height:16px;"></i>
                    {{ __('Total liens') }}
                </div>
                <div class="fw-bold fs-3">{{ number_format($stats['total']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 text-success small mb-1">
                    <i data-lucide="check-circle" style="width:16px;height:16px;"></i>
                    {{ __('Actifs') }}
                </div>
                <div class="fw-bold fs-3">{{ number_format($stats['active']) }}</div>
                <div class="text-muted small">{{ number_format($stats['inactive']) }} {{ __('inactifs') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 text-warning small mb-1">
                    <i data-lucide="alert-triangle" style="width:16px;height:16px;"></i>
                    {{ __('Expirés') }}
                </div>
                <div class="fw-bold fs-3">{{ number_format($stats['expired']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 text-primary small mb-1">
                    <i data-lucide="mouse-pointer-click" style="width:16px;height:16px;"></i>
                    {{ __('Total clics') }}
                </div>
                <div class="fw-bold fs-3">{{ number_format($stats['total_clicks']) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        @if($shortUrls->isEmpty())
            <div class="text-center py-5">
                <i data-lucide="link" style="width:48px;height:48px;color:#adb5bd;" class="mb-3"></i>
                <p class="text-muted mb-3">Aucun lien court pour le moment.</p>
                <a href="{{ route('admin.short-urls.create') }}" class="btn btn-primary btn-sm">Créer votre premier lien</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Lien court</th>
                            <th>URL originale</th>
                            <th>Titre</th>
                            <th class="text-center">Clics</th>
                            <th class="text-center">Statut</th>
                            <th>Créé le</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($shortUrls as $shortUrl)
                        <tr>
                            <td>
                                <code class="text-primary">/s/{{ $shortUrl->slug }}</code>
                                <button type="button" class="btn btn-link btn-sm p-0 ms-1" title="Copier le lien"
                                        aria-label="Copier le lien court"
                                        onclick="navigator.clipboard.writeText('{{ url('/s/' . $shortUrl->slug) }}');this.innerHTML='<i data-lucide=&quot;check&quot; style=&quot;width:14px;height:14px;&quot;></i>';setTimeout(()=>{this.innerHTML='<i data-lucide=&quot;copy&quot; style=&quot;width:14px;height:14px;&quot;></i>';lucide.createIcons()},1500);lucide.createIcons();">
                                    <i data-lucide="copy" style="width:14px;height:14px;"></i>
                                </button>
                            </td>
                            <td>
                                <a href="{{ $shortUrl->original_url }}" target="_blank" rel="noopener" class="text-decoration-none" title="{{ $shortUrl->original_url }}">
                                    {{ Str::limit($shortUrl->original_url, 50) }}
                                </a>
                            </td>
                            <td>{{ $shortUrl->title ?? '-' }}</td>
                            <td class="text-center fw-semibold">{{ number_format($shortUrl->clicks_count) }}</td>
                            <td class="text-center">
                                @if($shortUrl->is_active)
                                    <span class="badge bg-success">Actif</span>
                                @else
                                    <span class="badge bg-secondary">Inactif</span>
                                @endif
                                @if($shortUrl->isExpired())
                                    <span class="badge bg-warning text-dark">Expiré</span>
                                @endif
                            </td>
                            <td>{{ format_date($shortUrl->created_at) }}</td>
                            <td class="text-end">
                                @include('core::components.admin-action-menu', ['actions' => [
                                    ['label' => __('Statistiques'), 'icon' => 'bar-chart-2', 'url' => route('admin.short-urls.show', $shortUrl)],
                                    ['label' => __('Modifier'), 'icon' => 'pencil', 'url' => route('admin.short-urls.edit', $shortUrl)],
                                    ['label' => $shortUrl->is_active ? __('Désactiver') : __('Activer'), 'icon' => $shortUrl->is_active ? 'eye-off' : 'eye', 'url' => route('admin.short-urls.toggle', $shortUrl), 'method' => 'POST'],
                                    ['divider' => true],
                                    ['label' => __('Supprimer'), 'icon' => 'trash-2', 'url' => route('admin.short-urls.destroy', $shortUrl), 'method' => 'DELETE', 'confirm' => __('Supprimer ce lien court ?'), 'danger' => true],
                                ]])
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($shortUrls->hasPages())
                <div class="card-footer py-3 px-4">
                    {{ $shortUrls->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
