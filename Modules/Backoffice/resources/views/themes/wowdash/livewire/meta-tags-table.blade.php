<div>
    {{-- Filtres --}}
    <div class="d-flex flex-wrap align-items-center gap-3 mb-20">
        <form class="navbar-search">
            <input type="text" wire:model.live="search" class="bg-base h-40-px w-auto" placeholder="Rechercher URL ou titre...">
            <iconify-icon icon="ion:search-outline" class="icon"></iconify-icon>
        </form>
        <select class="form-select form-select-sm w-auto ps-12 py-6 radius-12 h-40-px" wire:model.live="filterActive">
            <option value="">Tous les statuts</option>
            <option value="1">Actif</option>
            <option value="0">Inactif</option>
        </select>
        @if($search || $filterActive !== '')
            <button class="btn btn-sm text-neutral-600 bg-neutral-100 bg-hover-neutral-200 radius-4 d-inline-flex align-items-center gap-1" wire:click="resetFilters">
                <iconify-icon icon="solar:restart-outline"></iconify-icon>Réinitialiser
            </button>
        @endif
    </div>

    {{-- Tableau --}}
    <div class="table-responsive scroll-sm">
        <table class="table bordered-table sm-table mb-0">
            <thead>
                <tr>
                    <th style="cursor:pointer;" wire:click="sort('url_pattern')">
                        URL Pattern
                        @if($sortBy === 'url_pattern')
                            <iconify-icon icon="solar:alt-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-outline" class="ms-1"></iconify-icon>
                        @endif
                    </th>
                    <th style="cursor:pointer;" wire:click="sort('title')">
                        Titre
                        @if($sortBy === 'title')
                            <iconify-icon icon="solar:alt-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-outline" class="ms-1"></iconify-icon>
                        @endif
                    </th>
                    <th>Robots</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($metaTags as $tag)
                    <tr>
                        <td>
                            <code class="text-primary-600">{{ $tag->url_pattern }}</code>
                        </td>
                        <td>
                            {{ $tag->title ? \Illuminate\Support\Str::limit($tag->title, 50) : '—' }}
                        </td>
                        <td>
                            <span class="bg-info-focus text-info-600 border border-info-main px-24 py-4 radius-4 fw-medium text-sm">
                                {{ $tag->robots ?? 'index, follow' }}
                            </span>
                        </td>
                        <td>
                            <div class="form-check form-switch switch-primary">
                                <input type="checkbox" class="form-check-input" role="switch"
                                    wire:click="toggleActive({{ $tag->id }})"
                                    @checked($tag->is_active)
                                    title="Cliquer pour {{ $tag->is_active ? 'désactiver' : 'activer' }}">
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="dropdown d-inline-block">
                                <button type="button" class="w-40-px h-40-px bg-neutral-200 text-secondary-light rounded-circle d-flex justify-content-center align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
                                    <iconify-icon icon="tabler:dots-vertical" class="icon text-xl"></iconify-icon>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-12">
                                    <a href="{{ route('admin.seo.edit', $tag) }}" class="dropdown-item d-flex align-items-center gap-2 px-12 py-8 rounded-4 text-sm">
                                        <iconify-icon icon="lucide:edit" class="icon text-lg text-success-600"></iconify-icon> Modifier
                                    </a>
                                    <form action="{{ route('admin.seo.destroy', $tag) }}" method="POST" onsubmit="return confirm('Supprimer ce tag SEO ?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="dropdown-item d-flex align-items-center gap-2 px-12 py-8 rounded-4 text-sm text-danger-600">
                                            <iconify-icon icon="fluent:delete-24-regular" class="icon text-lg"></iconify-icon> Supprimer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-secondary-light py-32">
                            <iconify-icon icon="solar:tag-price-outline" class="icon text-4xl mb-2 d-block"></iconify-icon>
                            Aucun tag SEO configuré
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $metaTags->links() }}
</div>
