<div>
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <div class="d-flex"><i class="ti ti-check me-2"></i>{{ session('success') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <div class="d-flex"><i class="ti ti-alert-circle me-2"></i>{{ session('error') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(count($selected) > 0)
    <div class="alert alert-info d-flex align-items-center gap-3 mb-3">
        <span>{{ count($selected) }} sélectionné(s)</span>
        <select wire:model.live="bulkAction" class="form-select form-select-sm" style="width:auto;">
            <option value="">Action groupée</option>
            <option value="activate">Activer</option>
            <option value="deactivate">Désactiver</option>
            <option value="delete">Supprimer</option>
        </select>
        <button wire:click="executeBulkAction" wire:confirm="Exécuter cette action ?" class="btn btn-sm btn-primary">Exécuter</button>
    </div>
    @endif

    <div class="row mb-3 g-2">
        <div class="col-md-4">
            <div class="input-icon">
                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Rechercher...">
            </div>
        </div>
        <div class="col-md-3">
            <select wire:model.live="filterStatus" class="form-select">
                <option value="">Tous les statuts</option>
                <option value="active">Actif</option>
                <option value="inactive">Inactif</option>
            </select>
        </div>
        <div class="col-md-3">
            <select wire:model.live="filterRole" class="form-select">
                <option value="">Tous les rôles</option>
                @foreach($availableRoles ?? [] as $role)
                <option value="{{ $role }}">{{ ucfirst($role) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button wire:click="resetFilters" class="btn btn-outline-secondary w-100"><i class="ti ti-x me-1"></i> Reset</button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter table-hover">
            <thead>
                <tr>
                    <th style="width:1%"><input type="checkbox" wire:model.live="selectAll" class="form-check-input"></th>
                    <th wire:click="sort('name')" style="cursor:pointer">
                        Nom
                        <i class="ti ti-arrows-sort {{ $sortBy === 'name' ? 'text-primary' : 'text-muted' }}"></i>
                    </th>
                    <th wire:click="sort('email')" style="cursor:pointer">
                        Courriel
                        <i class="ti ti-arrows-sort {{ $sortBy === 'email' ? 'text-primary' : 'text-muted' }}"></i>
                    </th>
                    <th>Rôles</th>
                    <th>Statut</th>
                    <th wire:click="sort('created_at')" style="cursor:pointer">
                        Inscrit le
                        <i class="ti ti-arrows-sort {{ $sortBy === 'created_at' ? 'text-primary' : 'text-muted' }}"></i>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td><input type="checkbox" wire:model.live="selected" value="{{ $user->id }}" class="form-check-input"></td>
                    <td x-data="{ editing: false, name: '{{ addslashes($user->name) }}' }">
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar avatar-sm rounded-circle" style="background-color: var(--tblr-primary); color: white; font-size: .75rem;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </span>
                            <span x-show="!editing" @dblclick="editing = true" style="cursor:pointer;" title="Double-clic pour modifier">{{ $user->name }}</span>
                            <input x-show="editing" x-model="name"
                                @keydown.enter="$wire.inlineUpdateName({{ $user->id }}, name); editing = false"
                                @keydown.escape="editing = false"
                                x-cloak class="form-control form-control-sm" style="width:200px;">
                        </div>
                    </td>
                    <td>{{ $user->email }}</td>
                    <td>
                        @foreach($user->roles as $role)
                        <span class="badge bg-primary-lt">{{ $role->name }}</span>
                        @endforeach
                    </td>
                    <td>
                        <label class="form-check form-switch mb-0">
                            <input type="checkbox" class="form-check-input"
                                wire:click="toggleActive({{ $user->id }})"
                                {{ $user->is_active ? 'checked' : '' }}>
                        </label>
                    </td>
                    <td class="text-muted">{{ $user->created_at->format('d/m/Y') }}</td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-ghost-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="ti ti-dots-vertical"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('admin.users.show', $user) }}">
                                    <i class="ti ti-eye me-2"></i> Voir
                                </a>
                                <a class="dropdown-item" href="{{ route('admin.users.edit', $user) }}">
                                    <i class="ti ti-edit me-2"></i> Modifier
                                </a>
                                @if(Route::has('admin.users.impersonate'))
                                <a class="dropdown-item" href="{{ route('admin.users.impersonate', $user) }}">
                                    <i class="ti ti-user-check me-2"></i> Impersoner
                                </a>
                                @endif
                                <div class="dropdown-divider"></div>
                                <button wire:click="deleteUser({{ $user->id }})" wire:confirm="Supprimer cet utilisateur ?" class="dropdown-item text-danger">
                                    <i class="ti ti-trash me-2"></i> Supprimer
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="ti ti-users-off fs-2 d-block mb-2"></i>
                        Aucun utilisateur trouvé
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex align-items-center justify-content-between mt-3">
        <div class="text-muted small">
            {{ $users->total() }} utilisateur(s) au total
        </div>
        <div>{{ $users->links() }}</div>
    </div>
</div>
