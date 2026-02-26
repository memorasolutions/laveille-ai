<div>
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2 mb-20">
            <iconify-icon icon="solar:check-circle-outline" class="icon text-lg"></iconify-icon>
            {{ session('success') }}
        </div>
    @endif

    {{-- Filtres --}}
    <div class="d-flex flex-wrap gap-3 mb-20">
        <select wire:model.live="filterStatus" class="form-select form-select-sm w-auto">
            <option value="">Tous les statuts</option>
            <option value="published">Publiés</option>
            <option value="draft">Brouillons</option>
        </select>
        @if($filterStatus || $search)
            <button wire:click="resetFilters" class="btn btn-sm btn-outline-secondary">
                <iconify-icon icon="solar:close-circle-outline" class="icon"></iconify-icon> Réinitialiser
            </button>
        @endif
    </div>

    {{-- Recherche --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-20">
        <div class="position-relative">
            <input type="text" wire:model.live.debounce.300ms="search"
                   class="form-control ps-40" placeholder="Rechercher une page...">
            <iconify-icon icon="ion:search-outline"
                          class="position-absolute top-50 start-0 translate-middle-y ms-12 text-secondary-light"></iconify-icon>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-responsive scroll-sm">
        <table class="table bordered-table sm-table mb-0">
            <thead>
                <tr>
                    <th wire:click="sort('title')" style="cursor: pointer;" class="user-select-none">
                        Titre
                        @if($sortBy === 'title')
                            <iconify-icon icon="{{ $sortDirection === 'asc' ? 'solar:alt-arrow-up-outline' : 'solar:alt-arrow-down-outline' }}" class="icon text-sm"></iconify-icon>
                        @endif
                    </th>
                    <th>Slug</th>
                    <th>Statut</th>
                    <th wire:click="sort('created_at')" style="cursor: pointer;" class="user-select-none">
                        Date
                        @if($sortBy === 'created_at')
                            <iconify-icon icon="{{ $sortDirection === 'asc' ? 'solar:alt-arrow-up-outline' : 'solar:alt-arrow-down-outline' }}" class="icon text-sm"></iconify-icon>
                        @endif
                    </th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pages as $page)
                    <tr>
                        <td><span class="fw-semibold">{{ $page->title }}</span></td>
                        <td><code class="text-secondary-light">{{ $page->slug }}</code></td>
                        <td>
                            @if($page->status === 'published')
                                <span class="bg-success-focus text-success-600 border border-success-main px-24 py-4 radius-4 fw-medium text-sm">Publié</span>
                            @else
                                <span class="bg-warning-focus text-warning-600 border border-warning-main px-24 py-4 radius-4 fw-medium text-sm">Brouillon</span>
                            @endif
                        </td>
                        <td>{{ $page->created_at->format('d/m/Y') }}</td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button type="button" class="w-40-px h-40-px bg-neutral-200 text-secondary-light rounded-circle d-flex justify-content-center align-items-center" data-bs-toggle="dropdown">
                                    <iconify-icon icon="tabler:dots-vertical" class="icon text-xl"></iconify-icon>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-12">
                                    <a href="{{ route('pages.show', $page->slug) }}" target="_blank" class="dropdown-item d-flex align-items-center gap-2">
                                        <iconify-icon icon="majesticons:eye-line" class="icon"></iconify-icon> Voir public
                                    </a>
                                    <a href="{{ route('admin.pages.edit', $page->slug) }}" class="dropdown-item d-flex align-items-center gap-2">
                                        <iconify-icon icon="lucide:edit" class="icon"></iconify-icon> Modifier
                                    </a>
                                    <button wire:click="deletePage({{ $page->id }})" wire:confirm="Confirmer la suppression ?" class="dropdown-item d-flex align-items-center gap-2 text-danger-600">
                                        <iconify-icon icon="fluent:delete-24-regular" class="icon"></iconify-icon> Supprimer
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-secondary-light py-20">Aucune page trouvée</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($pages->hasPages())
        <div class="mt-20">{{ $pages->links() }}</div>
    @endif
</div>
