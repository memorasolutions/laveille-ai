<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
            <i data-lucide="check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    {{-- Filtres et recherche --}}
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <div class="position-relative flex-grow-1" style="min-width:200px;">
            <input type="text" wire:model.live.debounce.300ms="search"
                   class="form-control ps-5"
                   placeholder="Rechercher une page...">
            <i data-lucide="search" class="position-absolute top-50 translate-middle-y ms-2" style="left:0;pointer-events:none;width:16px;height:16px;"></i>
        </div>
        <select wire:model.live="filterStatus" class="form-select" style="width:auto;">
            <option value="">Tous les statuts</option>
            <option value="published">Publiés</option>
            <option value="draft">Brouillons</option>
        </select>
        @if($filterStatus || $search)
            <button wire:click="resetFilters" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                <i data-lucide="x"></i> Réinitialiser
            </button>
        @endif
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th class="cursor-pointer" wire:click="sort('title')" style="cursor:pointer;user-select:none;">
                        <span class="d-flex align-items-center gap-1">
                            Titre
                            @if($sortBy === 'title')
                                <i data-lucide="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}"></i>
                            @else
                                <i data-lucide="chevrons-up-down" class="text-muted"></i>
                            @endif
                        </span>
                    </th>
                    <th>Slug</th>
                    <th>Statut</th>
                    <th style="cursor:pointer;user-select:none;" wire:click="sort('created_at')">
                        <span class="d-flex align-items-center gap-1">
                            Date
                            @if($sortBy === 'created_at')
                                <i data-lucide="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}"></i>
                            @else
                                <i data-lucide="chevrons-up-down" class="text-muted"></i>
                            @endif
                        </span>
                    </th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pages as $page)
                    <tr>
                        <td class="fw-medium">{{ $page->title }}</td>
                        <td>
                            <code class="small bg-light text-muted px-2 py-1 rounded">{{ $page->slug }}</code>
                        </td>
                        <td>
                            @if($page->status === 'published')
                                <span class="badge bg-success bg-opacity-10 text-success">Publié</span>
                            @else
                                <span class="badge bg-warning bg-opacity-10 text-warning">Brouillon</span>
                            @endif
                        </td>
                        <td class="text-muted">{{ format_date($page->created_at) }}</td>
                        <td>
                            @if($confirmingDeleteId === $page->id)
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <span class="text-danger small fw-semibold">{{ __('Confirmer ?') }}</span>
                                    <button type="button" wire:click="deletePage({{ $page->id }})" class="btn btn-sm btn-danger">{{ __('Oui') }}</button>
                                    <button type="button" wire:click="cancelDeletePage" class="btn btn-sm btn-outline-secondary">{{ __('Annuler') }}</button>
                                </div>
                            @else
                                <div class="d-flex align-items-center justify-content-center">
                                    {{-- 2026-08-31 (#2092) : $page->slug était un accès brut au slug traduisible. --}}
                                    @include('core::components.action-menu', ['actions' => array_filter([
                                        ['label' => __('Voir public'), 'icon' => 'eye', 'url' => $page->getPublicUrl(), 'target' => '_blank'],
                                        auth()->user()?->can('update_pages') ? ['label' => __('Modifier'), 'icon' => 'pencil', 'url' => route('admin.pages.edit', $page->resolveTranslatedSlug())] : null,
                                        (auth()->user()?->can('update_pages') || auth()->user()?->can('delete_pages')) ? ['divider' => true] : null,
                                        auth()->user()?->can('delete_pages') ? ['label' => __('Supprimer'), 'icon' => 'trash-2', 'wireClick' => "confirmDeletePage({$page->id})", 'danger' => true] : null,
                                    ])])
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-4 text-center text-muted">Aucune page trouvée</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($pages->hasPages())
        @include('backoffice::partials.infinite-scroll', ['paginator' => $pages])
    @endif
</div>
