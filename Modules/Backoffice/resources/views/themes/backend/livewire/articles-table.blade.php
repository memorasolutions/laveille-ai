<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2" role="alert">
            <i data-lucide="check-circle" class="icon-sm"></i>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
            <i data-lucide="alert-triangle" class="icon-sm"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- Bulk Actions Bar --}}
    @if(count($selected) > 0)
        <div class="d-flex flex-wrap align-items-center gap-3 mb-3 px-3 py-2 bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded">
            <span class="fw-medium text-body">{{ count($selected) }} {{ __('sélectionné(s)') }}</span>
            <select wire:model.live="bulkAction" class="form-select form-select-sm w-auto" aria-label="Action groupée">
                <option value="">{{ __('Choisir une action') }}</option>
                <option value="publish">{{ __('Publier') }}</option>
                <option value="draft">{{ __('Brouillon') }}</option>
                <option value="archive">{{ __('Archiver') }}</option>
                <option value="delete">{{ __('Supprimer') }}</option>
            </select>
            <button wire:click="executeBulkAction" wire:confirm="{{ __('Confirmer l\'action en masse ?') }}"
                    class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
                <i data-lucide="play-circle" class="icon-sm"></i> {{ __('Exécuter') }}
            </button>
        </div>
    @endif

    {{-- Filtres --}}
    <div class="border-bottom pb-3 mb-3">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <div class="input-group input-group-sm" style="width:220px">
                <span class="input-group-text">
                    <i data-lucide="search" class="icon-sm"></i>
                </span>
                <input type="text" wire:model.live.debounce.300ms="search"
                       class="form-control"
                       placeholder="{{ __('Rechercher un article...') }}"
                       aria-label="Rechercher">
            </div>
            <select wire:model.live="filterStatus" class="form-select form-select-sm w-auto" aria-label="Filtrer par statut">
                <option value="">{{ __('Tous les statuts') }}</option>
                <option value="published">{{ __('Publié') }}</option>
                <option value="draft">{{ __('Brouillon') }}</option>
                <option value="archived">{{ __('Archivé') }}</option>
            </select>
            <select wire:model.live="filterCategory" class="form-select form-select-sm w-auto" aria-label="Filtrer par catégorie">
                <option value="">{{ __('Toutes les catégories') }}</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
            <button wire:click="resetFilters"
                    class="btn btn-sm btn-light d-inline-flex align-items-center gap-1">
                <i data-lucide="x-circle" class="icon-sm"></i> {{ __('Réinitialiser') }}
            </button>
            <a href="{{ route('admin.blog.articles.create') }}"
               class="ms-auto btn btn-sm btn-primary d-inline-flex align-items-center gap-2">
                <i data-lucide="plus" class="icon-sm"></i> {{ __('Ajouter') }}
            </a>
        </div>
    </div>

    {{-- Table --}}
    @if($articles->isEmpty())
        <div class="text-center py-5">
            <i data-lucide="file-text" class="d-block mx-auto mb-3 text-muted" style="width:48px;height:48px"></i>
            <p class="text-muted">{{ __('Aucun article') }}</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr class="border-bottom">
                        <th style="width:40px">
                            <input type="checkbox" wire:model.live="selectAll" class="form-check-input" style="cursor:pointer" aria-label="Tout sélectionner">
                        </th>
                        <th class="fw-medium" style="width:64px">{{ __('Image') }}</th>
                        <th class="fw-medium user-select-none" style="cursor:pointer" wire:click="sort('title')">
                            {{ __('Titre') }}
                            @if($sortBy === 'title')
                                <i data-lucide="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="icon-sm ms-1 text-primary"></i>
                            @else
                                <i data-lucide="chevrons-up-down" class="icon-sm ms-1 text-muted"></i>
                            @endif
                        </th>
                        <th class="fw-medium" style="width:112px">{{ __('Statut') }}</th>
                        <th class="fw-medium" style="width:128px">{{ __('Catégorie') }}</th>
                        <th class="fw-medium" style="width:128px">{{ __('Auteur') }}</th>
                        <th class="fw-medium user-select-none" style="width:112px;cursor:pointer" wire:click="sort('created_at')">
                            {{ __('Date') }}
                            @if($sortBy === 'created_at')
                                <i data-lucide="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="icon-sm ms-1 text-primary"></i>
                            @else
                                <i data-lucide="chevrons-up-down" class="icon-sm ms-1 text-muted"></i>
                            @endif
                        </th>
                        <th class="fw-medium" style="width:144px">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($articles as $article)
                    <tr>
                        <td>
                            <input type="checkbox" wire:model.live="selected" value="{{ $article->id }}" class="form-check-input" style="cursor:pointer" aria-label="Sélectionner">
                        </td>
                        <td>
                            @if($article->featured_image)
                                <img src="{{ asset($article->featured_image) }}" class="rounded object-fit-cover" style="width:48px;height:48px" alt="">
                            @else
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:48px;height:48px">
                                    <i data-lucide="image" class="icon-sm text-muted"></i>
                                </div>
                            @endif
                        </td>
                        <td class="fw-medium text-body">{{ $article->title }}</td>
                        <td>
                            <select wire:change="changeStatus({{ $article->id }}, $event.target.value)"
                                    class="form-select form-select-sm w-auto"
                                    aria-label="Changer le statut">
                                <option value="draft" @selected($article->status === 'draft')>{{ __('Brouillon') }}</option>
                                <option value="published" @selected($article->status === 'published')>{{ __('Publié') }}</option>
                                <option value="archived" @selected($article->status === 'archived')>{{ __('Archivé') }}</option>
                            </select>
                        </td>
                        <td class="text-muted">{{ $article->blogCategory?->name ?? '–' }}</td>
                        <td class="text-muted">{{ $article->user?->name ?? '–' }}</td>
                        <td class="text-muted">{{ $article->created_at->format('d/m/Y') }}</td>
                        <td>
                            <div class="dropdown" x-data="{ open: false }" @click.outside="open = false">
                                <button @click="open = !open"
                                        class="btn btn-sm btn-light rounded-circle d-flex align-items-center justify-content-center"
                                        style="width:32px;height:32px">
                                    <i data-lucide="more-vertical" class="icon-sm"></i>
                                </button>
                                <div class="dropdown-menu" :class="{ show: open }" x-show="open" x-cloak
                                     @click.outside="open = false"
                                     style="min-width:140px">
                                    <a href="{{ route('admin.blog.articles.edit', $article) }}"
                                       class="dropdown-item d-flex align-items-center gap-2">
                                        <i data-lucide="pencil" class="icon-sm text-success"></i> {{ __('Modifier') }}
                                    </a>
                                    <form action="{{ route('admin.blog.articles.destroy', $article) }}" method="POST"
                                          onsubmit="return confirm('{{ __('Supprimer cet article ?') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="dropdown-item d-flex align-items-center gap-2 text-danger">
                                            <i data-lucide="trash-2" class="icon-sm"></i> {{ __('Supprimer') }}
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

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 pt-3 border-top">
            <span class="text-muted">{{ $articles->total() }} {{ __('entrée(s)') }}</span>
            {{ $articles->links() }}
        </div>
    @endif
</div>
