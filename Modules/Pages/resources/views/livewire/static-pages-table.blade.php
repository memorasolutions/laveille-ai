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
                        <td class="text-muted">{{ $page->created_at->format('d/m/Y') }}</td>
                        <td>
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <a href="{{ route('page.show', $page->slug) }}" target="_blank"
                                   class="btn btn-sm btn-outline-secondary rounded-circle p-1"
                                   title="Voir public" style="width:30px;height:30px;">
                                    <i data-lucide="eye"></i>
                                </a>
                                <a href="{{ route('admin.pages.edit', $page->slug) }}"
                                   class="btn btn-sm btn-outline-primary rounded-circle p-1"
                                   title="Modifier" style="width:30px;height:30px;">
                                    <i data-lucide="pen-line"></i>
                                </a>
                                <button wire:click="deletePage({{ $page->id }})"
                                        wire:confirm="Confirmer la suppression ?"
                                        class="btn btn-sm btn-outline-danger rounded-circle p-1"
                                        title="Supprimer" style="width:30px;height:30px;">
                                    <i data-lucide="trash-2"></i>
                                </button>
                            </div>
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
        <div class="mt-3">{{ $pages->links() }}</div>
    @endif
</div>
