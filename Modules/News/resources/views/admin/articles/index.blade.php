@extends('backoffice::layouts.admin', ['title' => 'Actualités — articles', 'subtitle' => $stats['total'] . ' articles, ' . $stats['published'] . ' publiés, ' . $stats['today'] . ' aujourd\'hui'])

@section('content')
    {{-- Compteurs --}}
    <div class="row mb-4">
        <div class="col-md-3"><div class="card bg-light"><div class="card-body text-center"><h3 class="mb-0">{{ $stats['total'] }}</h3><small class="text-muted">Total</small></div></div></div>
        <div class="col-md-3"><div class="card bg-success text-white"><div class="card-body text-center"><h3 class="mb-0">{{ $stats['published'] }}</h3><small>Publiés</small></div></div></div>
        <div class="col-md-3"><div class="card bg-warning text-dark"><div class="card-body text-center"><h3 class="mb-0">{{ $stats['filtered'] }}</h3><small>Filtrés</small></div></div></div>
        <div class="col-md-3"><div class="card bg-primary text-white"><div class="card-body text-center"><h3 class="mb-0">{{ $stats['today'] }}</h3><small>Aujourd'hui</small></div></div></div>
    </div>

    {{-- Filtres --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Publiés</option>
                        <option value="filtered" {{ request('status') === 'filtered' ? 'selected' : '' }}>Filtrés</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Catégorie</label>
                    <select name="category" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="feed" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <option value="ia" {{ request('feed') === 'ia' ? 'selected' : '' }}>IA</option>
                        <option value="techno" {{ request('feed') === 'techno' ? 'selected' : '' }}>Techno</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Recherche</label>
                    <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Titre...">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Table articles --}}
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>Score</th>
                        <th>Titre</th>
                        <th>Catégorie</th>
                        <th>Source</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($articles as $a)
                    <tr>
                        <td>
                            @if($a->relevance_score)
                                <span class="badge {{ $a->relevance_score >= 8 ? 'bg-success' : ($a->relevance_score >= 7 ? 'bg-primary' : 'bg-secondary') }}">
                                    {{ $a->relevance_score }}/10
                                </span>
                            @else
                                <span class="badge bg-light text-muted">—</span>
                            @endif
                        </td>
                        <td style="max-width: 300px;">
                            <a href="{{ route('news.show', $a) }}" target="_blank" style="font-weight: 600; color: var(--bs-body-color); text-decoration: none;">
                                {{ Str::limit($a->seo_title ?? $a->title, 60) }}
                            </a>
                            @if($a->score_justification)
                                <div style="font-size: 11px; color: #6b7280;">{{ $a->score_justification }}</div>
                            @endif
                        </td>
                        <td>
                            @if($a->category_tag)
                                <span class="badge bg-info text-dark">{{ $a->category_tag }}</span>
                            @endif
                            @if($a->feed_type)
                                <span class="badge bg-light text-muted">{{ $a->feed_type }}</span>
                            @endif
                        </td>
                        <td><small>{{ $a->source->name ?? '—' }}</small></td>
                        <td>
                            @if($a->is_published)
                                <span class="badge bg-success">Publié</span>
                            @else
                                <span class="badge bg-warning text-dark">Filtré</span>
                            @endif
                        </td>
                        <td><small>{{ $a->pub_date?->format('d/m H:i') }}</small></td>
                        <td class="text-end">
                            @include('core::components.admin-action-menu', ['actions' => [
                                ['label' => __('Voir l\'article'), 'icon' => 'external-link', 'url' => route('news.show', $a), 'target' => '_blank'],
                                ['label' => __('Modifier'), 'icon' => 'edit', 'url' => route('admin.news.articles.edit', $a)],
                                ['label' => __('Source originale'), 'icon' => 'link', 'url' => $a->url, 'target' => '_blank'],
                                ['label' => __('Re-scorer IA'), 'icon' => 'refresh-cw', 'url' => route('admin.news.articles.rescore', $a), 'method' => 'POST', 'confirm' => __('Re-scorer cet article via IA?')],
                                ['label' => $a->is_published ? __('Dépublier') : __('Publier'), 'icon' => $a->is_published ? 'pause' : 'play', 'url' => route('admin.news.articles.toggle', $a), 'method' => 'PATCH'],
                                ['divider' => true],
                                ['label' => __('Supprimer'), 'icon' => 'trash-2', 'url' => route('admin.news.articles.destroy', $a), 'method' => 'DELETE', 'confirm' => __('Supprimer cet article?'), 'danger' => true],
                            ]])
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucun article</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($articles->hasPages())
            <div class="card-footer d-flex justify-content-center">{{ $articles->links() }}</div>
        @endif
    </div>
@endsection
