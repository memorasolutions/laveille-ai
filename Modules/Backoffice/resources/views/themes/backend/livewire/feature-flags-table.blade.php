<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    {{-- Recherche --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div class="input-group input-group-sm" style="width:220px;">
            <span class="input-group-text">
                <i data-lucide="search" class="icon-sm text-muted"></i>
            </span>
            <input type="text" wire:model.live.debounce.300ms="search"
                   class="form-control"
                   placeholder="Rechercher une feature..."
                   aria-label="Rechercher">
        </div>
    </div>

    {{-- Features connues non encore activées --}}
    @php
        $existingNames = $features->pluck('name')->toArray();
        $pendingFeatures = array_diff($knownFeatures, $existingNames);
    @endphp

    @if(count($pendingFeatures) > 0 && !$search)
        <div class="card border border-dashed mb-3">
            <div class="card-header border-bottom border-dashed py-2 px-3">
                <h6 class="text-muted d-flex align-items-center gap-2 fw-medium mb-0 small">
                    <i data-lucide="flag" class="icon-sm"></i>
                    Features connues (non activées)
                </h6>
            </div>
            <div class="card-body py-2 px-3">
                <div class="d-flex flex-wrap gap-2">
                    @foreach($pendingFeatures as $name)
                        <form action="{{ route('admin.feature-flags.toggle', ['name' => $name]) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="btn btn-sm btn-success d-inline-flex align-items-center gap-1">
                                <i data-lucide="plus-circle" class="icon-sm"></i>
                                {{ $name }}
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Tableau features existantes --}}
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th class="fw-medium py-2 px-2">Nom</th>
                    <th class="fw-medium py-2 px-2">Conditions</th>
                    <th class="fw-medium py-2 px-2">Statut</th>
                </tr>
            </thead>
            <tbody>
                @forelse($features as $feature)
                    @php
                        $isActive = $feature->value === 'true';
                        $condition = $conditions[$feature->name] ?? null;
                        $currentType = $condition?->condition_type ?? 'always';
                        $colorMap = [
                            'always'      => 'text-success bg-success bg-opacity-10 border border-success border-opacity-25',
                            'percentage'  => 'text-info bg-info bg-opacity-10 border border-info border-opacity-25',
                            'roles'       => 'text-warning bg-warning bg-opacity-10 border border-warning border-opacity-25',
                            'environment' => 'text-primary bg-primary bg-opacity-10 border border-primary border-opacity-25',
                            'schedule'    => 'text-danger bg-danger bg-opacity-10 border border-danger border-opacity-25',
                        ];
                    @endphp
                    <tr>
                        <td class="py-2 px-2 align-middle">
                            <code class="small text-primary bg-primary bg-opacity-10 px-2 py-1 rounded">{{ $feature->name }}</code>
                        </td>
                        <td class="py-2 px-2 align-middle" style="min-width: 280px;">
                            @if($editingCondition === $feature->name)
                                {{-- Formulaire d'édition inline --}}
                                <div class="d-flex flex-column gap-3">
                                    <select class="form-select form-select-sm"
                                            wire:model.live="conditionType"
                                            aria-label="Type de condition">
                                        @foreach($availableTypes as $type => $label)
                                            <option value="{{ $type }}">{{ $label }}</option>
                                        @endforeach
                                    </select>

                                    @if($conditionType === 'always')
                                        <small class="text-muted d-flex align-items-center gap-1">
                                            <i data-lucide="info" class="icon-sm"></i>
                                            Toujours actif quand activé
                                        </small>
                                    @elseif($conditionType === 'percentage')
                                        <div>
                                            <label class="small text-muted d-block mb-1">
                                                Déploiement : <strong>{{ $conditionConfig['percentage'] ?? 0 }}%</strong>
                                            </label>
                                            <input type="range" class="form-range" min="0" max="100" step="5"
                                                   wire:model.live="conditionConfig.percentage"
                                                   aria-label="Pourcentage de déploiement">
                                        </div>
                                    @elseif($conditionType === 'roles')
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($roles as $role)
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input" type="checkbox"
                                                           id="role_{{ $role }}"
                                                           value="{{ $role }}"
                                                           wire:model="conditionConfig.roles">
                                                    <label class="form-check-label" for="role_{{ $role }}">{{ $role }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    @elseif($conditionType === 'environment')
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach(['local', 'staging', 'production'] as $env)
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input" type="checkbox"
                                                           id="env_{{ $env }}"
                                                           value="{{ $env }}"
                                                           wire:model="conditionConfig.environments">
                                                    <label class="form-check-label" for="env_{{ $env }}">{{ ucfirst($env) }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    @elseif($conditionType === 'schedule')
                                        <div class="row g-2">
                                            <div class="col">
                                                <label class="small text-muted d-block mb-1">Début</label>
                                                <input type="date" class="form-control form-control-sm"
                                                       wire:model="conditionConfig.start_date"
                                                       aria-label="Date de début">
                                            </div>
                                            <div class="col">
                                                <label class="small text-muted d-block mb-1">Fin</label>
                                                <input type="date" class="form-control form-control-sm"
                                                       wire:model="conditionConfig.end_date"
                                                       aria-label="Date de fin">
                                            </div>
                                        </div>
                                    @endif

                                    <div class="d-flex gap-2">
                                        <button type="button"
                                                class="btn btn-sm btn-success d-inline-flex align-items-center gap-1"
                                                wire:click="saveCondition">
                                            <i data-lucide="check" class="icon-sm"></i> Enregistrer
                                        </button>
                                        <button type="button"
                                                class="btn btn-sm btn-light text-muted"
                                                wire:click="cancelEdit">
                                            Annuler
                                        </button>
                                    </div>
                                </div>
                            @else
                                {{-- Affichage badge + bouton modifier --}}
                                <div class="d-flex align-items-center gap-2">
                                    @if($condition)
                                        <span class="badge rounded small fw-medium d-inline-flex align-items-center gap-1 {{ $colorMap[$currentType] ?? 'text-muted bg-light border' }}">
                                            {{ $availableTypes[$currentType] ?? $currentType }}
                                            @if($currentType === 'percentage')
                                                ({{ $condition->condition_config['percentage'] ?? 0 }}%)
                                            @elseif($currentType === 'roles')
                                                ({{ implode(', ', $condition->condition_config['roles'] ?? []) }})
                                            @elseif($currentType === 'environment')
                                                ({{ implode(', ', $condition->condition_config['environments'] ?? []) }})
                                            @elseif($currentType === 'schedule')
                                                ({{ $condition->condition_config['start_date'] ?? '' }} - {{ $condition->condition_config['end_date'] ?? '' }})
                                            @endif
                                        </span>
                                    @else
                                        <span class="text-muted">Aucune condition</span>
                                    @endif
                                    <button type="button"
                                            class="btn btn-sm btn-light d-inline-flex align-items-center justify-content-center p-0 text-muted"
                                            style="width:24px;height:24px;"
                                            wire:click="editCondition('{{ $feature->name }}')"
                                            title="Modifier les conditions">
                                        <i data-lucide="pencil" class="icon-sm"></i>
                                    </button>
                                </div>
                            @endif
                        </td>
                        <td class="py-2 px-2 align-middle">
                            <form action="{{ route('admin.feature-flags.toggle', ['name' => $feature->name]) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="badge {{ $isActive ? 'bg-success' : 'bg-danger' }} border-0 px-2 py-1 fw-semibold"
                                        title="{{ $isActive ? 'Cliquer pour désactiver' : 'Cliquer pour activer' }}"
                                        style="cursor:pointer;">
                                    {{ $isActive ? 'Actif' : 'Inactif' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="py-5 text-center text-muted">
                            <i data-lucide="flag" class="d-block mb-2 mx-auto text-muted" style="width:36px;height:36px;"></i>
                            Aucune feature flag configurée
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($features->hasPages())
        <div class="mt-3">{{ $features->links() }}</div>
    @endif
</div>
