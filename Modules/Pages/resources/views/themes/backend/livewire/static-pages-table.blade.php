<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
            <i data-lucide="check-circle" class="icon-sm"></i>
            {{ session('success') }}
        </div>
    @endif

    {{-- Filtres et recherche --}}
    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
        <div class="input-group flex-grow-1" style="min-width:200px;">
            <span class="input-group-text"><i data-lucide="search" class="icon-sm"></i></span>
            <input type="text" wire:model.live.debounce.300ms="search"
                   class="form-control"
                   placeholder="Rechercher une page...">
        </div>
        <select wire:model.live="filterStatus" class="form-select" style="width:auto;max-width:180px;">
            <option value="">Tous les statuts</option>
            <option value="published">Publiés</option>
            <option value="draft">Brouillons</option>
        </select>
        @if($filterStatus || $search)
            <button wire:click="resetFilters" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2">
                <i data-lucide="x" class="icon-sm"></i> Réinitialiser
            </button>
        @endif
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th class="py-3 px-4 fw-semibold text-body cursor-pointer select-none" wire:click="sort('title')">
                        <span class="d-flex align-items-center gap-2">
                            Titre
                            @if($sortBy === 'title')
                                @if($sortDirection === 'asc')
                                    <i data-lucide="arrow-up" class="icon-sm text-primary"></i>
                                @else
                                    <i data-lucide="arrow-down" class="icon-sm text-primary"></i>
                                @endif
                            @else
                                <i data-lucide="arrows-up-down" class="icon-sm text-muted"></i>
                            @endif
                        </span>
                    </th>
                    <th class="py-3 px-4 fw-semibold text-body">Slug</th>
                    <th class="py-3 px-4 fw-semibold text-body">Statut</th>
                    <th class="py-3 px-4 fw-semibold text-body cursor-pointer select-none" wire:click="sort('created_at')">
                        <span class="d-flex align-items-center gap-2">
                            Date
                            @if($sortBy === 'created_at')
                                @if($sortDirection === 'asc')
                                    <i data-lucide="arrow-up" class="icon-sm text-primary"></i>
                                @else
                                    <i data-lucide="arrow-down" class="icon-sm text-primary"></i>
                                @endif
                            @else
                                <i data-lucide="arrows-up-down" class="icon-sm text-muted"></i>
                            @endif
                        </span>
                    </th>
                    <th class="py-3 px-4 fw-semibold text-body text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pages as $page)
                    <tr>
                        <td class="py-3 px-4 fw-semibold text-body">{{ $page->title }}</td>
                        <td class="py-3 px-4">
                            <code>{{ $page->slug }}</code>
                        </td>
                        <td class="py-3 px-4">
                            @if($page->status === 'published')
                                <span class="badge bg-success">Publié</span>
                            @else
                                <span class="badge bg-warning text-dark">Brouillon</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-muted">{{ format_date($page->created_at) }}</td>
                        <td class="py-3 px-4">
                            @if($confirmingDeleteId === $page->id)
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <span class="text-danger small fw-semibold">{{ __('Confirmer ?') }}</span>
                                    <button type="button" wire:click="deletePage({{ $page->id }})" class="btn btn-sm btn-danger">{{ __('Oui') }}</button>
                                    <button type="button" wire:click="cancelDeletePage" class="btn btn-sm btn-outline-secondary">{{ __('Annuler') }}</button>
                                </div>
                            @else
                                <div class="d-flex align-items-center justify-content-center">
                                    @include('core::components.admin-action-menu', ['actions' => [
                                        ['label' => __('Voir public'), 'icon' => 'eye', 'url' => route('page.show', $page->slug), 'target' => '_blank'],
                                        ['label' => __('Modifier'), 'icon' => 'pencil', 'url' => route('admin.pages.edit', $page->slug)],
                                        ['divider' => true],
                                        ['label' => __('Supprimer'), 'icon' => 'trash-2', 'wireClick' => "confirmDeletePage({$page->id})", 'danger' => true],
                                    ]])
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-5 text-center text-muted">Aucune page trouvée</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($pages->hasPages())
        <div class="mt-4">{{ $pages->links() }}</div>
    @endif
</div>
