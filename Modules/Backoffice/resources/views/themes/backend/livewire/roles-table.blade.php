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

    {{-- Recherche --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div class="input-group" style="width:220px">
            <span class="input-group-text">
                <i data-lucide="search" class="icon-sm"></i>
            </span>
            <input type="text" wire:model.live.debounce.300ms="search"
                   class="form-control form-control-sm"
                   placeholder="Rechercher un rôle..."
                   aria-label="Rechercher">
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
                    <th class="fw-medium user-select-none" style="cursor:pointer" wire:click="sort('name')">
                        Nom
                        @if($sortBy === 'name')
                            <i data-lucide="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="icon-sm ms-1 text-primary"></i>
                        @else
                            <i data-lucide="chevrons-up-down" class="icon-sm ms-1 text-muted"></i>
                        @endif
                    </th>
                    <th class="fw-medium">Permissions</th>
                    <th class="fw-medium">Utilisateurs</th>
                    <th class="fw-medium">Guard</th>
                    <th class="fw-medium text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                    <tr>
                        <td class="fw-semibold text-body">{{ $role->name }}</td>
                        <td>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                {{ $role->permissions_count }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">
                                {{ $role->users_count }}
                            </span>
                        </td>
                        <td class="text-muted">{{ $role->guard_name }}</td>
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
                                    <a href="{{ route('admin.roles.show', $role) }}"
                                       class="dropdown-item d-flex align-items-center gap-2">
                                        <i data-lucide="eye" class="icon-sm text-info"></i> Voir
                                    </a>
                                    <a href="{{ route('admin.roles.edit', $role) }}"
                                       class="dropdown-item d-flex align-items-center gap-2">
                                        <i data-lucide="pencil" class="icon-sm text-success"></i> Modifier
                                    </a>
                                    @unless(in_array($role->name, ['super_admin', 'admin']))
                                        <form action="{{ route('admin.roles.destroy', $role) }}" method="POST"
                                              onsubmit="return confirm('Confirmer la suppression ?')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="dropdown-item d-flex align-items-center gap-2 text-danger">
                                                <i data-lucide="trash-2" class="icon-sm"></i> Supprimer
                                            </button>
                                        </form>
                                    @endunless
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-5 text-center text-muted">
                            <i data-lucide="shield" class="d-block mx-auto mb-2" style="width:32px;height:32px"></i>
                            Aucun rôle trouvé
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($roles->hasPages())
        <div class="mt-3">{{ $roles->links() }}</div>
    @endif
</div>
