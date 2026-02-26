<div>
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

    @if(count($selected) > 0)
        <div class="d-flex align-items-center gap-3 mb-20 p-12 bg-primary-50 rounded-8 border border-primary-100">
            <span class="text-sm fw-medium">{{ count($selected) }} {{ __('sélectionné(s)') }}</span>
            <select wire:model.live="bulkAction" class="form-select form-select-sm w-auto">
                <option value="">{{ __('Choisir une action') }}</option>
                <option value="activate">{{ __('Activer') }}</option>
                <option value="deactivate">{{ __('Désactiver') }}</option>
                <option value="delete">{{ __('Supprimer') }}</option>
            </select>
            <button wire:click="executeBulkAction" wire:confirm="{{ __('Confirmer l\'action en masse ?') }}" class="btn btn-sm btn-primary-600 d-inline-flex align-items-center gap-1">
                <iconify-icon icon="solar:play-circle-outline" class="icon text-xl"></iconify-icon> {{ __('Exécuter') }}
            </button>
        </div>
    @endif

    <div class="card-body border-bottom pb-16">
        <div class="d-flex flex-wrap align-items-center gap-3 mb-20">
            <form class="navbar-search">
                <input type="text" wire:model.live.debounce.300ms="search" class="bg-base h-40-px w-auto" placeholder="Rechercher une catégorie...">
                <iconify-icon icon="ion:search-outline" class="icon"></iconify-icon>
            </form>
            <select wire:model.live="filterActive" class="form-select form-select-sm w-auto ps-12 py-6 radius-12 h-40-px">
                <option value="">Tous les statuts</option>
                <option value="1">Actif</option>
                <option value="0">Inactif</option>
            </select>
            <button wire:click="resetFilters" class="btn btn-sm text-neutral-600 bg-neutral-100 bg-hover-neutral-200 radius-4">
                Réinitialiser
            </button>
        </div>
    </div>

    <div class="table-responsive scroll-sm">
        <table class="table bordered-table sm-table mb-0">
            <thead>
                <tr>
                    <th style="width:40px"><input type="checkbox" wire:model.live="selectAll" class="form-check-input"></th>
                    <th>Nom</th>
                    <th>Couleur</th>
                    <th>Articles</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                <tr>
                    <td><input type="checkbox" wire:model.live="selected" value="{{ $category->id }}" class="form-check-input"></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="d-inline-block rounded-circle flex-shrink-0"
                                  style="width:12px;height:12px;background:{{ $category->color }}"></span>
                            {{ $category->name }}
                        </div>
                    </td>
                    <td>
                        <span class="badge text-white" style="background-color:{{ $category->color }}">
                            {{ $category->color }}
                        </span>
                    </td>
                    <td>
                        <span class="bg-primary-50 text-primary-600 border border-primary-main px-24 py-4 radius-4 fw-medium text-sm">{{ $category->articles_count }}</span>
                    </td>
                    <td>
                        <div class="form-check form-switch switch-primary">
                            <input type="checkbox" class="form-check-input" role="switch"
                                wire:click="toggleActive({{ $category->id }})"
                                @checked($category->is_active)
                                title="Cliquer pour {{ $category->is_active ? 'désactiver' : 'activer' }}">
                        </div>
                    </td>
                    <td>
                        <div class="dropdown">
                            <button type="button" class="w-40-px h-40-px bg-neutral-200 text-secondary-light rounded-circle d-flex justify-content-center align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
                                <iconify-icon icon="tabler:dots-vertical" class="icon text-xl"></iconify-icon>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-12">
                                <a href="{{ route('admin.blog.categories.edit', $category) }}" class="dropdown-item d-flex align-items-center gap-2 px-12 py-8 rounded-4 text-sm">
                                    <iconify-icon icon="lucide:edit" class="icon text-lg text-success-600"></iconify-icon> Modifier
                                </a>
                                <form action="{{ route('admin.blog.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Supprimer cette catégorie ?')">
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
                    <td colspan="6" class="text-center text-neutral-600 py-32">
                        <iconify-icon icon="solar:tag-outline" class="icon text-4xl mb-2 d-block"></iconify-icon>
                        Aucune catégorie
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span class="text-neutral-600 text-sm">{{ $categories->total() }} catégorie(s)</span>
        {{ $categories->links() }}
    </div>
</div>
