<div>
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg p-3 mb-4 flex items-center gap-2">
            <i class="fa fa-check-circle text-sm"></i>
            {{ session('success') }}
        </div>
    @endif

    {{-- Filtres et recherche --}}
    <div class="flex flex-wrap items-center gap-3 mb-4">
        <div class="relative flex-1 min-w-[200px]">
            <input type="text" wire:model.live.debounce.300ms="search"
                   class="w-full border border-border rounded-lg pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                   placeholder="Rechercher une page...">
            <i class="fa fa-search absolute left-3 top-1/2 -translate-y-1/2 text-secondary text-xs"></i>
        </div>
        <select wire:model.live="filterStatus" class="border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
            <option value="">Tous les statuts</option>
            <option value="published">Publiés</option>
            <option value="draft">Brouillons</option>
        </select>
        @if($filterStatus || $search)
            <button wire:click="resetFilters" class="btn btn-outline text-sm px-3 py-2 rounded-lg flex items-center gap-1.5">
                <i class="fa fa-times text-xs"></i> Réinitialiser
            </button>
        @endif
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-border">
                    <th class="text-left py-3 px-4 font-medium text-heading cursor-pointer select-none" wire:click="sort('title')">
                        <span class="flex items-center gap-1">
                            Titre
                            @if($sortBy === 'title')
                                <i class="fa fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-xs text-primary"></i>
                            @else
                                <i class="fa fa-sort text-xs text-secondary"></i>
                            @endif
                        </span>
                    </th>
                    <th class="text-left py-3 px-4 font-medium text-heading">Slug</th>
                    <th class="text-left py-3 px-4 font-medium text-heading">Statut</th>
                    <th class="text-left py-3 px-4 font-medium text-heading cursor-pointer select-none" wire:click="sort('created_at')">
                        <span class="flex items-center gap-1">
                            Date
                            @if($sortBy === 'created_at')
                                <i class="fa fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-xs text-primary"></i>
                            @else
                                <i class="fa fa-sort text-xs text-secondary"></i>
                            @endif
                        </span>
                    </th>
                    <th class="text-center py-3 px-4 font-medium text-heading">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($pages as $page)
                    <tr class="hover:bg-neutral-50 transition-colors">
                        <td class="py-3 px-4 font-medium text-heading">{{ $page->title }}</td>
                        <td class="py-3 px-4">
                            <code class="text-xs bg-neutral-100 text-secondary px-2 py-0.5 rounded">{{ $page->slug }}</code>
                        </td>
                        <td class="py-3 px-4">
                            @if($page->status === 'published')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Publié</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Brouillon</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-secondary">{{ $page->created_at->format('d/m/Y') }}</td>
                        <td class="py-3 px-4">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('pages.show', $page->slug) }}" target="_blank"
                                   class="p-1.5 rounded-lg text-secondary hover:text-primary hover:bg-primary/10 transition-colors"
                                   title="Voir public">
                                    <i class="fa fa-eye text-sm"></i>
                                </a>
                                <a href="{{ route('admin.pages.edit', $page->slug) }}"
                                   class="p-1.5 rounded-lg text-secondary hover:text-primary hover:bg-primary/10 transition-colors"
                                   title="Modifier">
                                    <i class="fa fa-pencil text-sm"></i>
                                </a>
                                <button wire:click="deletePage({{ $page->id }})"
                                        wire:confirm="Confirmer la suppression ?"
                                        class="p-1.5 rounded-lg text-secondary hover:text-red-600 hover:bg-red-50 transition-colors"
                                        title="Supprimer">
                                    <i class="fa fa-trash text-sm"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-secondary">Aucune page trouvée</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($pages->hasPages())
        <div class="mt-4">{{ $pages->links() }}</div>
    @endif
</div>
