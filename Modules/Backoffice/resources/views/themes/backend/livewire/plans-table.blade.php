<div>
    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2 mb-3" role="alert">
            <i data-lucide="check-circle" class="icon-sm flex-shrink-0"></i>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert">
            <i data-lucide="alert-triangle" class="icon-sm flex-shrink-0"></i>
            {{ session('error') }}
        </div>
    @endif

    @if(count($selected) > 0)
        <div class="d-flex flex-wrap align-items-center gap-3 mb-3 px-3 py-2 bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded">
            <span class="fw-medium text-body">{{ count($selected) }} {{ __('sélectionné(s)') }}</span>
            <select wire:model.live="bulkAction" class="form-select form-select-sm w-auto" aria-label="Action groupée">
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
    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
        <div class="input-group input-group-sm w-auto">
            <span class="input-group-text bg-white border-end-0">
                <i data-lucide="search" class="icon-sm text-muted"></i>
            </span>
            <input type="text" wire:model.live="search"
                   class="form-control form-control-sm border-start-0"
                   placeholder="Rechercher..."
                   aria-label="Rechercher">
        </div>
        <select wire:model.live="filterInterval" class="form-select form-select-sm w-auto" aria-label="Filtrer par intervalle">
            <option value="">Tous les intervalles</option>
            <option value="monthly">Mensuel</option>
            <option value="yearly">Annuel</option>
            <option value="one_time">Paiement unique</option>
        </select>
        <select wire:model.live="filterActive" class="form-select form-select-sm w-auto" aria-label="Filtrer par statut">
            <option value="">Tous les statuts</option>
            <option value="1">Actif</option>
            <option value="0">Inactif</option>
        </select>
        @if($search || $filterInterval || $filterActive !== '')
            <button wire:click="resetFilters"
                    class="btn btn-sm btn-light d-inline-flex align-items-center gap-1">
                <i data-lucide="refresh-cw" class="icon-sm"></i> Réinitialiser
            </button>
        @endif
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr class="border-bottom">
                    <th style="width:40px;">
                        <input type="checkbox" wire:model.live="selectAll" class="form-check-input" style="width:16px;height:16px;" aria-label="Tout sélectionner">
                    </th>
                    <th class="fw-medium user-select-none" role="button" wire:click="sort('name')">
                        Nom
                        @if($sortBy === 'name')
                            <i data-lucide="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="icon-sm ms-1 text-primary"></i>
                        @else
                            <i data-lucide="chevrons-up-down" class="icon-sm ms-1 text-muted"></i>
                        @endif
                    </th>
                    <th class="fw-medium user-select-none" role="button" wire:click="sort('price')">
                        Prix
                        @if($sortBy === 'price')
                            <i data-lucide="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="icon-sm ms-1 text-primary"></i>
                        @else
                            <i data-lucide="chevrons-up-down" class="icon-sm ms-1 text-muted"></i>
                        @endif
                    </th>
                    <th class="fw-medium">Intervalle</th>
                    <th class="fw-medium">Essai</th>
                    <th class="fw-medium">Statut</th>
                    <th class="fw-medium text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($plans as $plan)
                    <tr>
                        <td>
                            <input type="checkbox" wire:model.live="selected" value="{{ $plan->id }}" class="form-check-input" style="width:16px;height:16px;" aria-label="Sélectionner">
                        </td>
                        <td>
                            <div class="fw-semibold text-body">{{ $plan->name }}</div>
                            @if($plan->description)
                                <small class="text-muted">{{ Str::limit($plan->description, 50) }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="fw-bold text-body">{{ number_format((float) $plan->price, 2) }}</span>
                            <span class="text-muted ms-1">{{ $plan->currency }}</span>
                        </td>
                        <td>
                            @switch($plan->interval)
                                @case('monthly')
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 fw-medium">Mensuel</span>
                                @break
                                @case('yearly')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fw-medium">Annuel</span>
                                @break
                                @case('one_time')
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 fw-medium">Unique</span>
                                @break
                            @endswitch
                        </td>
                        <td>
                            @if($plan->trial_days > 0)
                                <span class="badge bg-light text-muted border fw-medium">{{ $plan->trial_days }}j</span>
                            @else
                                <span class="text-muted">–</span>
                            @endif
                        </td>
                        <td>
                            <button type="button"
                                    wire:click="toggleActive({{ $plan->id }})"
                                    class="badge {{ $plan->is_active ? 'bg-success' : 'bg-danger' }} border-0 px-2 py-1 fw-semibold"
                                    title="{{ $plan->is_active ? 'Cliquer pour désactiver' : 'Cliquer pour activer' }}"
                                    style="cursor:pointer;font-size:0.75rem;">
                                {{ $plan->is_active ? 'Actif' : 'Inactif' }}
                            </button>
                        </td>
                        <td class="text-end">
                            <div class="position-relative d-inline-block" x-data="{ open: false }" @click.outside="open = false">
                                <button @click="open = !open"
                                        class="btn btn-sm btn-light rounded-circle d-inline-flex align-items-center justify-content-center"
                                        style="width:32px;height:32px;">
                                    <i data-lucide="more-vertical" class="icon-sm"></i>
                                </button>
                                <div x-show="open" x-cloak
                                     class="dropdown-menu show position-absolute end-0 mt-1 shadow"
                                     style="z-index:50;min-width:140px;">
                                    <a href="{{ route('admin.plans.edit', $plan) }}"
                                       class="dropdown-item d-flex align-items-center gap-2 text-body">
                                        <i data-lucide="pencil" class="icon-sm text-success"></i> Modifier
                                    </a>
                                    <form action="{{ route('admin.plans.destroy', $plan) }}" method="POST"
                                          onsubmit="return confirm('Supprimer ce plan ?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="dropdown-item d-flex align-items-center gap-2 text-danger">
                                            <i data-lucide="trash-2" class="icon-sm"></i> Supprimer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-5 text-center text-muted">
                            <i data-lucide="tag" class="d-block mx-auto mb-2 text-muted" style="width:32px;height:32px;opacity:.4;"></i>
                            Aucun plan trouvé
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $plans->links() }}
</div>
