<div>
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2 mb-20">
            <iconify-icon icon="solar:check-circle-outline" class="icon text-lg"></iconify-icon>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-20">
            <iconify-icon icon="solar:danger-circle-outline" class="icon text-lg"></iconify-icon>
            {{ session('error') }}
        </div>
    @endif

    {{-- Bulk Actions Bar --}}
    @if(count($selected) > 0)
        <div class="d-flex align-items-center gap-3 mb-20 p-12 bg-primary-50 rounded-8 border border-primary-100">
            <span class="text-sm fw-medium">{{ count($selected) }} sélectionné(s)</span>
            <select wire:model.live="bulkAction" class="form-select form-select-sm w-auto">
                <option value="">Choisir une action</option>
                <option value="publish">{{ __('Publier') }}</option>
                <option value="draft">{{ __('Brouillon') }}</option>
                <option value="archive">{{ __('Archiver') }}</option>
                <option value="delete">{{ __('Supprimer') }}</option>
            </select>
            <button wire:click="executeBulkAction" wire:confirm="Confirmer l'action en masse ?" class="btn btn-sm btn-primary-600 d-inline-flex align-items-center gap-1">
                <iconify-icon icon="solar:play-circle-outline" class="icon text-xl"></iconify-icon> Exécuter
            </button>
        </div>
    @endif

    {{-- Filtres --}}
    <div class="card-body border-bottom pb-16">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <form class="navbar-search">
                <input type="text" wire:model.live.debounce.300ms="search" class="bg-base h-40-px w-auto" placeholder="Rechercher un article...">
                <iconify-icon icon="ion:search-outline" class="icon"></iconify-icon>
            </form>
            <select wire:model.live="filterStatus" class="form-select form-select-sm w-auto ps-12 py-6 radius-12 h-40-px">
                    <option value="">Tous les statuts</option>
                    <option value="published">Publié</option>
                    <option value="draft">Brouillon</option>
                    <option value="archived">Archivé</option>
                </select>
            <select wire:model.live="filterCategory" class="form-select form-select-sm w-auto ps-12 py-6 radius-12 h-40-px">
                    <option value="">Toutes les catégories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            <button wire:click="resetFilters" class="btn btn-sm text-neutral-600 bg-neutral-100 bg-hover-neutral-200 radius-4 d-inline-flex align-items-center gap-1">
                <iconify-icon icon="solar:close-circle-outline" class="icon"></iconify-icon> Réinitialiser
            </button>
            <a href="{{ route('admin.blog.articles.create') }}" class="btn btn-sm btn-primary-600 radius-8 d-flex align-items-center gap-2 ms-auto">
                <iconify-icon icon="ic:baseline-plus" class="icon text-xl line-height-1"></iconify-icon> Ajouter
            </a>
        </div>
    </div>

    {{-- Table --}}
    <div class="card-body p-0">
        @if($articles->isEmpty())
            <div class="text-center py-40">
                <iconify-icon icon="solar:document-outline" class="text-6xl text-secondary-light mb-16"></iconify-icon>
                <p class="text-secondary-light">Aucun article</p>
            </div>
        @else
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table sm-table mb-0">
                    <thead>
                        <tr>
                            <th style="width:40px"><input type="checkbox" wire:model.live="selectAll" class="form-check-input"></th>
                            <th style="width:70px">Image</th>
                            <th>
                                <button wire:click="sort('title')" class="btn btn-link p-0 text-decoration-none text-inherit fw-semibold">
                                    Titre
                                    @if($sortBy === 'title')
                                        <iconify-icon icon="{{ $sortDirection === 'asc' ? 'solar:arrow-up-outline' : 'solar:arrow-down-outline' }}"></iconify-icon>
                                    @endif
                                </button>
                            </th>
                            <th style="width:110px">Statut</th>
                            <th style="width:130px">Catégorie</th>
                            <th style="width:130px">Auteur</th>
                            <th style="width:110px">
                                <button wire:click="sort('created_at')" class="btn btn-link p-0 text-decoration-none text-inherit fw-semibold">
                                    Date
                                    @if($sortBy === 'created_at')
                                        <iconify-icon icon="{{ $sortDirection === 'asc' ? 'solar:arrow-up-outline' : 'solar:arrow-down-outline' }}"></iconify-icon>
                                    @endif
                                </button>
                            </th>
                            <th style="width:150px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($articles as $article)
                        <tr>
                            <td><input type="checkbox" wire:model.live="selected" value="{{ $article->id }}" class="form-check-input"></td>
                            <td>
                                @if($article->featured_image)
                                    <img src="{{ Storage::url($article->featured_image) }}" width="60" class="rounded">
                                @else
                                    <span class="text-secondary-light">–</span>
                                @endif
                            </td>
                            <td class="text-sm fw-medium">{{ $article->title }}</td>
                            <td>
                                <select wire:change="changeStatus({{ $article->id }}, $event.target.value)" class="form-select form-select-sm radius-4" style="width:auto;min-width:120px;">
                                    <option value="draft" @selected($article->status === 'draft')>Brouillon</option>
                                    <option value="published" @selected($article->status === 'published')>Publié</option>
                                    <option value="archived" @selected($article->status === 'archived')>Archivé</option>
                                </select>
                            </td>
                            <td class="text-sm text-secondary-light">{{ $article->blogCategory?->name ?? '–' }}</td>
                            <td class="text-sm text-secondary-light">{{ $article->user?->name ?? '–' }}</td>
                            <td class="text-sm text-secondary-light">{{ $article->created_at->format('d/m/Y') }}</td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="w-40-px h-40-px bg-neutral-200 text-secondary-light rounded-circle d-flex justify-content-center align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
                                        <iconify-icon icon="tabler:dots-vertical" class="icon text-xl"></iconify-icon>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end p-12">
                                        <a href="{{ route('admin.blog.articles.edit', $article) }}" class="dropdown-item d-flex align-items-center gap-2 px-12 py-8 rounded-4 text-sm">
                                            <iconify-icon icon="lucide:edit" class="icon text-lg text-success-600"></iconify-icon> Modifier
                                        </a>
                                        <form action="{{ route('admin.blog.articles.destroy', $article) }}" method="POST" onsubmit="return confirm('Supprimer cet article ?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="dropdown-item d-flex align-items-center gap-2 px-12 py-8 rounded-4 text-sm text-danger-600">
                                                <iconify-icon icon="fluent:delete-24-regular" class="icon text-lg"></iconify-icon> Supprimer
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span class="text-secondary-light text-sm">{{ $articles->total() }} entrée(s)</span>
                {{ $articles->links() }}
            </div>
        @endif
    </div>
</div>
