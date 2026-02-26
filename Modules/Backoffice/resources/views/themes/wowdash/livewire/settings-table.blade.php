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

    {{-- Filtres --}}
    <div class="d-flex flex-wrap gap-3 mb-20">
        <select wire:model.live="filterGroup" class="form-select form-select-sm w-auto">
            <option value="">Tous les groupes</option>
            @foreach($groups as $group)
                <option value="{{ $group }}">{{ $group }}</option>
            @endforeach
        </select>
        @if($filterGroup || $search)
            <button wire:click="resetFilters" class="btn btn-sm text-neutral-600 bg-neutral-100 bg-hover-neutral-200 radius-4 d-inline-flex align-items-center gap-1">
                <iconify-icon icon="solar:close-circle-outline" class="icon"></iconify-icon> Réinitialiser
            </button>
        @endif
    </div>

    {{-- Search --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-20">
        <div class="position-relative">
            <input type="text" wire:model.live.debounce.300ms="search" class="form-control ps-40" placeholder="Rechercher une clé ou valeur...">
            <iconify-icon icon="ion:search-outline" class="position-absolute top-50 start-0 translate-middle-y ms-12 text-secondary-light"></iconify-icon>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-responsive scroll-sm">
        <table class="table bordered-table sm-table mb-0">
            <thead>
                <tr>
                    <th wire:click="sort('key')" style="cursor: pointer;" class="user-select-none">
                        Clé
                        @if($sortBy === 'key')
                            <iconify-icon icon="{{ $sortDirection === 'asc' ? 'solar:alt-arrow-up-outline' : 'solar:alt-arrow-down-outline' }}" class="icon text-sm"></iconify-icon>
                        @endif
                    </th>
                    <th>Valeur</th>
                    <th>Groupe</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($settings as $setting)
                    <tr>
                        <td><code>{{ $setting->key }}</code></td>
                        <td class="text-secondary-light">{{ Str::limit($setting->value, 60) }}</td>
                        <td>
                            @if($setting->group)
                                <span class="bg-info-focus text-info-600 border border-info-main px-24 py-4 radius-4 fw-medium text-sm">{{ $setting->group }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button type="button" class="w-40-px h-40-px bg-neutral-200 text-secondary-light rounded-circle d-flex justify-content-center align-items-center" data-bs-toggle="dropdown">
                                    <iconify-icon icon="tabler:dots-vertical" class="icon text-xl"></iconify-icon>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-12">
                                    <a href="{{ route('admin.settings.edit', $setting) }}" class="dropdown-item d-flex align-items-center gap-2">
                                        <iconify-icon icon="lucide:edit" class="icon"></iconify-icon> Modifier
                                    </a>
                                    <form action="{{ route('admin.settings.destroy', $setting) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-danger-600" onclick="return confirm('Confirmer la suppression ?')">
                                            <iconify-icon icon="fluent:delete-24-regular" class="icon"></iconify-icon> Supprimer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-secondary-light py-20">Aucun paramètre trouvé</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($settings->hasPages())
        <div class="mt-20">{{ $settings->links() }}</div>
    @endif
</div>
