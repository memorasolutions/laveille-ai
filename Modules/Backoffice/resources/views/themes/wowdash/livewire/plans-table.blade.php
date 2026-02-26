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

    {{-- Filtres --}}
    <div class="d-flex flex-wrap align-items-center gap-3 mb-20">
        <form class="navbar-search">
            <input type="text" wire:model.live="search" class="bg-base h-40-px w-auto" placeholder="Rechercher...">
            <iconify-icon icon="ion:search-outline" class="icon"></iconify-icon>
        </form>

        <select class="form-select form-select-sm w-auto ps-12 py-6 radius-12 h-40-px" wire:model.live="filterInterval">
            <option value="">Tous les intervalles</option>
            <option value="monthly">Mensuel</option>
            <option value="yearly">Annuel</option>
            <option value="one_time">Paiement unique</option>
        </select>

        <select class="form-select form-select-sm w-auto ps-12 py-6 radius-12 h-40-px" wire:model.live="filterActive">
            <option value="">Tous les statuts</option>
            <option value="1">Actif</option>
            <option value="0">Inactif</option>
        </select>

        @if($search || $filterInterval || $filterActive !== '')
            <button class="btn btn-sm text-neutral-600 bg-neutral-100 bg-hover-neutral-200 radius-4 d-inline-flex align-items-center gap-1" wire:click="resetFilters">
                <iconify-icon icon="solar:restart-outline"></iconify-icon> Réinitialiser
            </button>
        @endif
    </div>

    {{-- Tableau --}}
    <div class="table-responsive scroll-sm">
        <table class="table bordered-table sm-table mb-0">
            <thead>
                <tr>
                    <th style="width:40px"><input type="checkbox" wire:model.live="selectAll" class="form-check-input"></th>
                    <th style="cursor:pointer" wire:click="sort('name')">
                        Nom
                        @if($sortBy === 'name')
                            <iconify-icon icon="{{ $sortDirection === 'asc' ? 'solar:sort-from-bottom-to-top-outline' : 'solar:sort-from-top-to-bottom-outline' }}"></iconify-icon>
                        @endif
                    </th>
                    <th style="cursor:pointer" wire:click="sort('price')">
                        Prix
                        @if($sortBy === 'price')
                            <iconify-icon icon="{{ $sortDirection === 'asc' ? 'solar:sort-from-bottom-to-top-outline' : 'solar:sort-from-top-to-bottom-outline' }}"></iconify-icon>
                        @endif
                    </th>
                    <th>Intervalle</th>
                    <th>Essai</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($plans as $plan)
                    <tr>
                        <td><input type="checkbox" wire:model.live="selected" value="{{ $plan->id }}" class="form-check-input"></td>
                        <td>
                            <div class="fw-semibold">{{ $plan->name }}</div>
                            @if($plan->description)
                                <small class="text-secondary-light">{{ Str::limit($plan->description, 50) }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="fw-bold">{{ number_format((float) $plan->price, 2) }}</span>
                            <span class="text-secondary-light">{{ $plan->currency }}</span>
                        </td>
                        <td>
                            @switch($plan->interval)
                                @case('monthly')  <span class="bg-info-focus text-info-600 border border-info-main px-24 py-4 radius-4 fw-medium text-sm">Mensuel</span>  @break
                                @case('yearly')   <span class="bg-success-focus text-success-600 border border-success-main px-24 py-4 radius-4 fw-medium text-sm">Annuel</span>  @break
                                @case('one_time') <span class="bg-warning-focus text-warning-600 border border-warning-main px-24 py-4 radius-4 fw-medium text-sm">Unique</span>  @break
                            @endswitch
                        </td>
                        <td>
                            @if($plan->trial_days > 0)
                                <span class="bg-neutral-200 text-neutral-600 border border-neutral-400 px-24 py-4 radius-4 fw-medium text-sm">{{ $plan->trial_days }}j</span>
                            @else
                                <span class="text-secondary-light">–</span>
                            @endif
                        </td>
                        <td>
                            <div class="form-check form-switch switch-primary">
                                <input type="checkbox" class="form-check-input" role="switch"
                                    wire:click="toggleActive({{ $plan->id }})"
                                    @checked($plan->is_active)
                                    title="Cliquer pour {{ $plan->is_active ? 'désactiver' : 'activer' }}">
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="dropdown d-inline-block">
                                <button type="button" class="w-40-px h-40-px bg-neutral-200 text-secondary-light rounded-circle d-flex justify-content-center align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
                                    <iconify-icon icon="tabler:dots-vertical" class="icon text-xl"></iconify-icon>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-12">
                                    <a href="{{ route('admin.plans.edit', $plan) }}" class="dropdown-item d-flex align-items-center gap-2 px-12 py-8 rounded-4 text-sm">
                                        <iconify-icon icon="lucide:edit" class="icon text-lg text-success-600"></iconify-icon> Modifier
                                    </a>
                                    <form action="{{ route('admin.plans.destroy', $plan) }}" method="POST" onsubmit="return confirm('Supprimer ce plan ?')">
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
                        <td colspan="7" class="text-center text-secondary-light py-32">
                            <iconify-icon icon="solar:tag-price-outline" class="icon text-4xl mb-2 d-block"></iconify-icon>
                            Aucun plan trouvé
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $plans->links() }}
</div>
