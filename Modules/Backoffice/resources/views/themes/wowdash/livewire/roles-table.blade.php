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

    {{-- Search --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-20">
        <div class="position-relative">
            <input type="text" wire:model.live.debounce.300ms="search" class="form-control ps-40" placeholder="Rechercher un rôle...">
            <iconify-icon icon="ion:search-outline" class="position-absolute top-50 start-0 translate-middle-y ms-12 text-secondary-light"></iconify-icon>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-responsive scroll-sm">
        <table class="table bordered-table sm-table mb-0">
            <thead>
                <tr>
                    <th wire:click="sort('name')" style="cursor: pointer;" class="user-select-none">
                        Nom
                        @if($sortBy === 'name')
                            <iconify-icon icon="{{ $sortDirection === 'asc' ? 'solar:alt-arrow-up-outline' : 'solar:alt-arrow-down-outline' }}" class="icon text-sm"></iconify-icon>
                        @endif
                    </th>
                    <th>Permissions</th>
                    <th>Utilisateurs</th>
                    <th>Guard</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                    <tr>
                        <td class="fw-semibold">{{ $role->name }}</td>
                        <td>
                            <span class="bg-primary-50 text-primary-600 border border-primary-main px-24 py-4 radius-4 fw-medium text-sm">{{ $role->permissions_count }}</span>
                        </td>
                        <td>
                            <span class="bg-info-focus text-info-600 border border-info-main px-24 py-4 radius-4 fw-medium text-sm">{{ $role->users_count }}</span>
                        </td>
                        <td>{{ $role->guard_name }}</td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button type="button" class="w-40-px h-40-px bg-neutral-200 text-secondary-light rounded-circle d-flex justify-content-center align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
                                    <iconify-icon icon="tabler:dots-vertical" class="icon text-xl"></iconify-icon>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-12">
                                    <a href="{{ route('admin.roles.show', $role) }}" class="dropdown-item d-flex align-items-center gap-2 px-12 py-8 rounded-4 text-sm">
                                        <iconify-icon icon="majesticons:eye-line" class="icon text-lg text-info-600"></iconify-icon> Voir
                                    </a>
                                    <a href="{{ route('admin.roles.edit', $role) }}" class="dropdown-item d-flex align-items-center gap-2 px-12 py-8 rounded-4 text-sm">
                                        <iconify-icon icon="lucide:edit" class="icon text-lg text-success-600"></iconify-icon> Modifier
                                    </a>
                                    @unless(in_array($role->name, ['super_admin', 'admin']))
                                        <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" onsubmit="return confirm('Confirmer la suppression ?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="dropdown-item d-flex align-items-center gap-2 px-12 py-8 rounded-4 text-sm text-danger-600">
                                                <iconify-icon icon="fluent:delete-24-regular" class="icon text-lg"></iconify-icon> Supprimer
                                            </button>
                                        </form>
                                    @endunless
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-secondary-light py-20">Aucun rôle trouvé</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($roles->hasPages())
        <div class="mt-20">{{ $roles->links() }}</div>
    @endif
</div>
