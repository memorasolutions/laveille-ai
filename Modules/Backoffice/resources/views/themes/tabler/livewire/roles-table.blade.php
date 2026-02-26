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

    <div class="mb-3">
        <div class="input-icon" style="max-width:300px;">
            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
            <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Rechercher un rôle...">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter table-hover">
            <thead>
                <tr>
                    <th wire:click="sort('name')" style="cursor:pointer">
                        Nom <i class="ti ti-arrows-sort {{ $sortBy === 'name' ? 'text-primary' : 'text-muted' }}"></i>
                    </th>
                    <th>Guard</th>
                    <th>Permissions</th>
                    <th>Utilisateurs</th>
                    <th wire:click="sort('created_at')" style="cursor:pointer">
                        Créé le <i class="ti ti-arrows-sort {{ $sortBy === 'created_at' ? 'text-primary' : 'text-muted' }}"></i>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar avatar-sm rounded bg-primary-lt">
                                <i class="ti ti-shield text-primary" style="font-size:.85rem;"></i>
                            </span>
                            <strong>{{ $role->name }}</strong>
                        </div>
                    </td>
                    <td><span class="badge bg-secondary-lt text-secondary">{{ $role->guard_name }}</span></td>
                    <td>
                        <span class="badge bg-primary-lt text-primary">
                            <i class="ti ti-key me-1"></i>{{ $role->permissions->count() }}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-azure-lt text-azure">
                            <i class="ti ti-users me-1"></i>{{ $role->users_count ?? $role->users()->count() }}
                        </span>
                    </td>
                    <td class="text-muted small">{{ $role->created_at->format('d/m/Y') }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.roles.show', $role) }}" class="btn btn-sm btn-outline-primary" title="Voir">
                                <i class="ti ti-eye"></i>
                            </a>
                            <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-outline-warning" title="Modifier">
                                <i class="ti ti-edit"></i>
                            </a>
                            @if($role->name !== 'super-admin')
                            <button wire:click="deleteRole({{ $role->id }})"
                                wire:confirm="Supprimer le rôle « {{ $role->name }} » ?"
                                class="btn btn-sm btn-outline-danger" title="Supprimer">
                                <i class="ti ti-trash"></i>
                            </button>
                            @else
                            <button class="btn btn-sm btn-outline-secondary" disabled title="Rôle protégé">
                                <i class="ti ti-lock"></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="ti ti-shield-off fs-2 d-block mb-2"></i>
                        Aucun rôle trouvé
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex align-items-center justify-content-between mt-3">
        <div class="text-muted small">{{ $roles->total() }} rôle(s) au total</div>
        <div>{{ $roles->links() }}</div>
    </div>
</div>
