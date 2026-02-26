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
                <option value="activate">Activer</option>
                <option value="deactivate">Désactiver</option>
                <option value="delete">Supprimer</option>
            </select>
            <button wire:click="executeBulkAction" wire:confirm="Confirmer l'action en masse ?" class="btn btn-sm btn-primary-600 d-inline-flex align-items-center gap-1">
                <iconify-icon icon="solar:play-circle-outline" class="icon text-xl"></iconify-icon> Exécuter
            </button>
        </div>
    @endif

    {{-- Filtres --}}
    <div class="d-flex flex-wrap gap-3 mb-20">
        <select wire:model.live="filterStatus" class="form-select form-select-sm w-auto">
            <option value="">Tous les statuts</option>
            <option value="active">Actifs</option>
            <option value="inactive">Inactifs</option>
        </select>
        <select wire:model.live="filterRole" class="form-select form-select-sm w-auto">
            <option value="">Tous les rôles</option>
            @foreach($roles as $role)
                <option value="{{ $role->name }}">{{ $role->name }}</option>
            @endforeach
        </select>
        @if($filterStatus || $filterRole || $search)
            <button wire:click="resetFilters" class="btn btn-sm text-neutral-600 bg-neutral-100 bg-hover-neutral-200 radius-4 d-inline-flex align-items-center gap-1">
                <iconify-icon icon="solar:close-circle-outline" class="icon"></iconify-icon> Réinitialiser
            </button>
        @endif
    </div>

    {{-- Search --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-20">
        <div class="position-relative">
            <input type="text" wire:model.live.debounce.300ms="search" class="form-control ps-40" placeholder="Rechercher un utilisateur...">
            <iconify-icon icon="ion:search-outline" class="position-absolute top-50 start-0 translate-middle-y ms-12 text-secondary-light"></iconify-icon>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-responsive scroll-sm">
        <table class="table bordered-table sm-table mb-0">
            <thead>
                <tr>
                    <th><input type="checkbox" wire:model.live="selectAll" class="form-check-input"></th>
                    <th wire:click="sort('name')" style="cursor: pointer;" class="user-select-none">
                        Nom
                        @if($sortBy === 'name')
                            <iconify-icon icon="{{ $sortDirection === 'asc' ? 'solar:alt-arrow-up-outline' : 'solar:alt-arrow-down-outline' }}" class="icon text-sm"></iconify-icon>
                        @endif
                    </th>
                    <th wire:click="sort('email')" style="cursor: pointer;" class="user-select-none">
                        Courriel
                        @if($sortBy === 'email')
                            <iconify-icon icon="{{ $sortDirection === 'asc' ? 'solar:alt-arrow-up-outline' : 'solar:alt-arrow-down-outline' }}" class="icon text-sm"></iconify-icon>
                        @endif
                    </th>
                    <th>Statut</th>
                    <th>Rôles</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td><input type="checkbox" wire:model.live="selected" value="{{ $user->id }}" class="form-check-input"></td>
                        <td>
                            <div class="d-flex align-items-center gap-3" x-data="{ editing: false, name: '{{ addslashes($user->name) }}' }">
                                <span class="w-36-px h-36-px bg-primary-100 text-primary-600 rounded-circle d-flex justify-content-center align-items-center fw-semibold flex-shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </span>
                                <span class="fw-semibold" x-show="!editing" @dblclick="editing = true" title="Double-clic pour modifier" style="cursor:text;">{{ $user->name }}</span>
                                <input type="text" class="form-control form-control-sm" x-show="editing" x-model="name"
                                    @blur="editing = false; $wire.inlineUpdateName({{ $user->id }}, name)"
                                    @keydown.enter="editing = false; $wire.inlineUpdateName({{ $user->id }}, name)"
                                    @keydown.escape="editing = false"
                                    x-effect="if(editing) $el.focus()">
                            </div>
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <div class="form-check form-switch switch-primary">
                                <input type="checkbox" class="form-check-input" role="switch"
                                    wire:click="toggleActive({{ $user->id }})"
                                    @checked($user->is_active)
                                    title="Cliquer pour {{ $user->is_active ? 'désactiver' : 'activer' }}">
                            </div>
                        </td>
                        <td>
                            @foreach($user->roles as $role)
                                <span class="bg-primary-50 text-primary-600 border border-primary-main px-24 py-4 radius-4 fw-medium text-sm">{{ $role->name }}</span>
                            @endforeach
                        </td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button type="button" class="w-40-px h-40-px bg-neutral-200 text-secondary-light rounded-circle d-flex justify-content-center align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
                                    <iconify-icon icon="tabler:dots-vertical" class="icon text-xl"></iconify-icon>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-12">
                                    <a href="{{ route('admin.users.show', $user) }}" class="dropdown-item d-flex align-items-center gap-2 px-12 py-8 rounded-4 text-sm">
                                        <iconify-icon icon="majesticons:eye-line" class="icon text-lg text-info-600"></iconify-icon> Voir
                                    </a>
                                    <a href="{{ route('admin.users.edit', $user) }}" class="dropdown-item d-flex align-items-center gap-2 px-12 py-8 rounded-4 text-sm">
                                        <iconify-icon icon="lucide:edit" class="icon text-lg text-success-600"></iconify-icon> Modifier
                                    </a>
                                    @if($user->id !== auth()->id())
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Confirmer la suppression ?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="dropdown-item d-flex align-items-center gap-2 px-12 py-8 rounded-4 text-sm text-danger-600">
                                                <iconify-icon icon="fluent:delete-24-regular" class="icon text-lg"></iconify-icon> Supprimer
                                            </button>
                                        </form>
                                    @endif
                                    @if(auth()->user()->hasRole('super_admin') && !$user->hasRole('super_admin') && $user->id !== auth()->id())
                                        <form action="{{ route('admin.users.impersonate', $user) }}" method="POST" onsubmit="return confirm('Impersoner {{ $user->name }} ?')">
                                            @csrf
                                            <button type="submit" class="dropdown-item d-flex align-items-center gap-2 px-12 py-8 rounded-4 text-sm">
                                                <iconify-icon icon="solar:user-speak-outline" class="icon text-lg text-neutral-600"></iconify-icon> Impersoner
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-secondary-light py-20">Aucun utilisateur trouvé</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div class="mt-20">{{ $users->links() }}</div>
    @endif
</div>
