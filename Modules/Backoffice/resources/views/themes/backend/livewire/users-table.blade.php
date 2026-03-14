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
            <select wire:model.live="bulkAction" class="form-select form-select-sm w-auto" aria-label="{{ __('Action groupée') }}">
                <option value="">{{ __('Choisir une action') }}</option>
                <option value="activate">{{ __('Activer') }}</option>
                <option value="deactivate">{{ __('Désactiver') }}</option>
                <option value="delete">{{ __('Supprimer') }}</option>
            </select>
            <button wire:click="executeBulkAction" wire:confirm="{{ __('Confirmer l\'action en masse ?') }}"
                    class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
                <i data-lucide="play-circle" class="icon-sm"></i> {{ __('Exécuter') }}
            </button>
        </div>
    @endif

    {{-- Filtres --}}
    <div class="d-flex flex-wrap gap-3 mb-3">
        <select wire:model.live="filterStatus" class="form-select form-select-sm w-auto" aria-label="{{ __('Filtrer par statut') }}">
            <option value="">{{ __('Tous les statuts') }}</option>
            <option value="active">{{ __('Actifs') }}</option>
            <option value="inactive">{{ __('Inactifs') }}</option>
        </select>
        <select wire:model.live="filterRole" class="form-select form-select-sm w-auto" aria-label="{{ __('Filtrer par rôle') }}">
            <option value="">{{ __('Tous les rôles') }}</option>
            @foreach($roles as $role)
                <option value="{{ $role->name }}">{{ $role->name }}</option>
            @endforeach
        </select>
        @if($filterStatus || $filterRole || $search)
            <button wire:click="resetFilters"
                    class="btn btn-sm btn-light d-inline-flex align-items-center gap-1">
                <i data-lucide="x-circle" class="icon-sm"></i> {{ __('Réinitialiser') }}
            </button>
        @endif
    </div>

    {{-- Barre de recherche + par page --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div class="input-group" style="width:220px">
            <span class="input-group-text">
                <i data-lucide="search" class="icon-sm"></i>
            </span>
            <input type="text" wire:model.live.debounce.300ms="search"
                   class="form-control form-control-sm"
                   placeholder="{{ __('Rechercher un utilisateur...') }}"
                   aria-label="{{ __('Rechercher') }}">
        </div>
        <select wire:model.live="perPage" class="form-select form-select-sm w-auto" aria-label="Éléments par page">
            <option value="10">10 / page</option>
            <option value="25">25 / page</option>
            <option value="50">50 / page</option>
        </select>
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr class="border-bottom">
                    <th>
                        <input type="checkbox" wire:model.live="selectAll" class="form-check-input" style="cursor:pointer" aria-label="Tout sélectionner">
                    </th>
                    <th class="fw-medium" style="cursor:pointer" wire:click="sort('name')">
                        {{ __('Nom') }}
                        @if($sortBy === 'name')
                            <i data-lucide="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="icon-sm ms-1 text-primary"></i>
                        @else
                            <i data-lucide="chevrons-up-down" class="icon-sm ms-1 text-muted"></i>
                        @endif
                    </th>
                    <th class="fw-medium" style="cursor:pointer" wire:click="sort('email')">
                        {{ __('Courriel') }}
                        @if($sortBy === 'email')
                            <i data-lucide="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="icon-sm ms-1 text-primary"></i>
                        @else
                            <i data-lucide="chevrons-up-down" class="icon-sm ms-1 text-muted"></i>
                        @endif
                    </th>
                    <th class="fw-medium">{{ __('Statut') }}</th>
                    <th class="fw-medium">{{ __('Rôles') }}</th>
                    <th class="fw-medium text-center">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>
                            <input type="checkbox" wire:model.live="selected" value="{{ $user->id }}" class="form-check-input" style="cursor:pointer" aria-label="Sélectionner">
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-3"
                                 x-data="{ editing: false, name: '{{ addslashes($user->name) }}' }">
                                <span class="d-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary fw-semibold flex-shrink-0"
                                      style="width:36px;height:36px">
                                    {{ mb_strtoupper(mb_substr($user->name, 0, 2)) }}
                                </span>
                                <span class="fw-medium" x-show="!editing"
                                      @dblclick="editing = true" title="Double-clic pour modifier" style="cursor:text;">
                                    {{ $user->name }}
                                </span>
                                <input type="text"
                                       class="form-control form-control-sm"
                                       style="width:144px"
                                       x-show="editing" x-model="name"
                                       aria-label="Modifier le nom"
                                       @blur="editing = false; $wire.inlineUpdateName({{ $user->id }}, name)"
                                       @keydown.enter="editing = false; $wire.inlineUpdateName({{ $user->id }}, name)"
                                       @keydown.escape="editing = false"
                                       x-effect="if(editing) $el.focus()">
                            </div>
                        </td>
                        <td class="text-muted">{{ $user->email }}</td>
                        <td>
                            <div class="form-check form-switch mb-0">
                                <input type="checkbox" class="form-check-input" role="switch"
                                       wire:click="toggleActive({{ $user->id }})"
                                       @checked($user->is_active)
                                       title="{{ $user->is_active ? __('Désactiver') : __('Activer') }}">
                            </div>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($user->roles as $role)
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                        {{ $role->name }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="dropdown" x-data="{ open: false }" @click.outside="open = false">
                                <button @click="open = !open"
                                        class="btn btn-sm btn-light rounded-circle d-flex align-items-center justify-content-center"
                                        style="width:32px;height:32px">
                                    <i data-lucide="more-vertical" class="icon-sm"></i>
                                </button>
                                <div class="dropdown-menu" :class="{ show: open }" x-show="open" x-cloak
                                     @click.outside="open = false"
                                     style="min-width:140px">
                                    <a href="{{ route('admin.users.show', $user) }}"
                                       class="dropdown-item d-flex align-items-center gap-2">
                                        <i data-lucide="eye" class="icon-sm text-info"></i> {{ __('Voir') }}
                                    </a>
                                    <a href="{{ route('admin.users.edit', $user) }}"
                                       class="dropdown-item d-flex align-items-center gap-2">
                                        <i data-lucide="pencil" class="icon-sm text-success"></i> {{ __('Modifier') }}
                                    </a>
                                    @if($user->id !== auth()->id())
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                              onsubmit="return confirm('{{ __('Confirmer la suppression ?') }}')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="dropdown-item d-flex align-items-center gap-2 text-danger">
                                                <i data-lucide="trash-2" class="icon-sm"></i> {{ __('Supprimer') }}
                                            </button>
                                        </form>
                                    @endif
                                    @if(auth()->user()->hasRole('super_admin') && !$user->hasRole('super_admin') && $user->id !== auth()->id())
                                        <form action="{{ route('admin.users.impersonate', $user) }}" method="POST"
                                              onsubmit="return confirm('{{ __('Impersoner') }} {{ $user->name }} ?')">
                                            @csrf
                                            <button type="submit"
                                                    class="dropdown-item d-flex align-items-center gap-2 text-body">
                                                <i data-lucide="user-check" class="icon-sm text-muted"></i> {{ __('Impersoner') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-5 text-center text-muted">
                            <i data-lucide="user" class="d-block mx-auto mb-2" style="width:32px;height:32px"></i>
                            {{ __('Aucun utilisateur trouvé') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div class="mt-3">{{ $users->links() }}</div>
    @endif
</div>
