<div>
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <div class="d-flex"><i class="ti ti-check me-2"></i>{{ session('success') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <div class="d-flex"><i class="ti ti-alert-circle me-2"></i>{{ session('error') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row mb-3 g-2">
        <div class="col-md-4">
            <div class="input-icon">
                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Rechercher un article...">
            </div>
        </div>
        <div class="col-md-3">
            <select wire:model.live="filterStatus" class="form-select">
                <option value="">Tous les statuts</option>
                <option value="draft">Brouillon</option>
                <option value="published">Publié</option>
                <option value="archived">Archivé</option>
            </select>
        </div>
        <div class="col-md-3">
            <select wire:model.live="filterCategory" class="form-select">
                <option value="">Toutes les catégories</option>
                @foreach($availableCategories ?? [] as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button wire:click="resetFilters" class="btn btn-outline-secondary w-100">
                <i class="ti ti-x me-1"></i> Reset
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter table-hover">
            <thead>
                <tr>
                    <th wire:click="sort('title')" style="cursor:pointer">
                        Titre <i class="ti ti-arrows-sort {{ $sortBy === 'title' ? 'text-primary' : 'text-muted' }}"></i>
                    </th>
                    <th>Catégorie</th>
                    <th>Statut</th>
                    <th>Auteur</th>
                    <th wire:click="sort('published_at')" style="cursor:pointer">
                        Date <i class="ti ti-arrows-sort {{ $sortBy === 'published_at' ? 'text-primary' : 'text-muted' }}"></i>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($articles as $article)
                <tr>
                    <td style="max-width: 280px;">
                        <div class="text-truncate fw-medium" title="{{ $article->getTranslation('title', app()->getLocale()) }}">
                            {{ $article->getTranslation('title', app()->getLocale()) }}
                        </div>
                        @if($article->featured_image)
                        <small class="text-muted"><i class="ti ti-photo me-1"></i>Image</small>
                        @endif
                    </td>
                    <td>
                        @if($article->category)
                        <span class="badge bg-secondary-lt">{{ $article->category->name }}</span>
                        @else
                        <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td>
                        @php
                            $statusMap = [
                                'published' => ['bg-success-lt text-success', 'ti-circle-check', 'Publié'],
                                'draft'     => ['bg-warning-lt text-warning', 'ti-pencil', 'Brouillon'],
                                'archived'  => ['bg-secondary-lt text-secondary', 'ti-archive', 'Archivé'],
                            ];
                            [$cls, $icon, $label] = $statusMap[$article->status] ?? ['bg-secondary-lt', 'ti-question-mark', ucfirst($article->status)];
                        @endphp
                        <span class="badge {{ $cls }}">
                            <i class="ti {{ $icon }} me-1"></i>{{ $label }}
                        </span>
                    </td>
                    <td>
                        @if($article->author)
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar avatar-xs rounded-circle bg-primary-lt" style="font-size:.65rem; width:22px; height:22px; display:inline-flex; align-items:center; justify-content:center;">
                                {{ strtoupper(substr($article->author->name, 0, 1)) }}
                            </span>
                            <span class="small">{{ $article->author->name }}</span>
                        </div>
                        @else
                        <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td class="text-muted small">
                        {{ $article->published_at ? $article->published_at->format('d/m/Y') : '—' }}
                    </td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-ghost-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="ti ti-dots-vertical"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('admin.articles.edit', $article) }}">
                                    <i class="ti ti-edit me-2"></i> Modifier
                                </a>
                                @if($article->status === 'published')
                                <a class="dropdown-item" href="{{ route('blog.show', $article->slug) }}" target="_blank">
                                    <i class="ti ti-external-link me-2"></i> Voir en ligne
                                </a>
                                @endif
                                <div class="dropdown-divider"></div>
                                <button wire:click="deleteArticle({{ $article->id }})"
                                    wire:confirm="Supprimer cet article ?"
                                    class="dropdown-item text-danger">
                                    <i class="ti ti-trash me-2"></i> Supprimer
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="ti ti-article-off fs-2 d-block mb-2"></i>
                        Aucun article trouvé
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex align-items-center justify-content-between mt-3">
        <div class="text-muted small">{{ $articles->total() }} article(s) au total</div>
        <div>{{ $articles->links() }}</div>
    </div>
</div>
