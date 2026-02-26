<div>
    {{-- Search --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-20">
        <div class="position-relative">
            <input type="text" wire:model.live.debounce.300ms="search" class="form-control ps-40" placeholder="Rechercher une feature...">
            <iconify-icon icon="ion:search-outline" class="position-absolute top-50 start-0 translate-middle-y ms-12 text-secondary-light"></iconify-icon>
        </div>
    </div>

    {{-- Features connues non encore activées --}}
    @php
        $existingNames = $features->pluck('name')->toArray();
        $pendingFeatures = array_diff($knownFeatures, $existingNames);
    @endphp

    @if(count($pendingFeatures) > 0 && !$search)
        <div class="card mb-4 border-dashed">
            <div class="card-header py-2">
                <h6 class="mb-0 text-secondary-light d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:flag-outline"></iconify-icon>
                    Features connues (non activées)
                </h6>
            </div>
            <div class="card-body py-3">
                <div class="d-flex flex-wrap gap-2">
                    @foreach($pendingFeatures as $name)
                        <form action="{{ route('admin.feature-flags.toggle', ['name' => $name]) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success-600 radius-4 d-inline-flex align-items-center gap-1">
                                <iconify-icon icon="solar:add-circle-outline"></iconify-icon>
                                {{ $name }}
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Tableau features existantes --}}
    <div class="table-responsive scroll-sm">
        <table class="table bordered-table sm-table mb-0">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Conditions</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @forelse($features as $feature)
                    @php
                        $isActive = $feature->value === 'true';
                        $condition = $conditions[$feature->name] ?? null;
                        $currentType = $condition?->condition_type ?? 'always';
                        $colorMap = [
                            'always'      => 'bg-success-focus text-success-main',
                            'percentage'  => 'bg-info-focus text-info-main',
                            'roles'       => 'bg-warning-focus text-warning-main',
                            'environment' => 'bg-primary-focus text-primary-main',
                            'schedule'    => 'bg-danger-focus text-danger-main',
                        ];
                    @endphp
                    <tr>
                        <td>
                            <code class="text-primary-600">{{ $feature->name }}</code>
                        </td>
                        <td style="min-width: 280px;">
                            @if($editingCondition === $feature->name)
                                {{-- Formulaire d'édition inline --}}
                                <div class="d-flex flex-column gap-12">
                                    <select class="form-select form-select-sm radius-8" wire:model.live="conditionType">
                                        @foreach($availableTypes as $type => $label)
                                            <option value="{{ $type }}">{{ $label }}</option>
                                        @endforeach
                                    </select>

                                    @if($conditionType === 'always')
                                        <small class="text-secondary-light">
                                            <iconify-icon icon="solar:info-circle-outline" class="icon"></iconify-icon>
                                            Toujours actif quand activé
                                        </small>
                                    @elseif($conditionType === 'percentage')
                                        <div>
                                            <label class="form-label text-sm text-secondary-light mb-4">
                                                Déploiement : <strong>{{ $conditionConfig['percentage'] ?? 0 }}%</strong>
                                            </label>
                                            <input type="range" class="form-range" min="0" max="100" step="5"
                                                wire:model.live="conditionConfig.percentage">
                                        </div>
                                    @elseif($conditionType === 'roles')
                                        <div class="d-flex flex-wrap gap-8">
                                            @foreach($roles as $role)
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox"
                                                        value="{{ $role }}"
                                                        id="role_{{ $feature->name }}_{{ $role }}"
                                                        wire:model="conditionConfig.roles">
                                                    <label class="form-check-label text-sm" for="role_{{ $feature->name }}_{{ $role }}">
                                                        {{ $role }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    @elseif($conditionType === 'environment')
                                        <div class="d-flex flex-wrap gap-8">
                                            @foreach(['local', 'staging', 'production'] as $env)
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox"
                                                        value="{{ $env }}"
                                                        id="env_{{ $feature->name }}_{{ $env }}"
                                                        wire:model="conditionConfig.environments">
                                                    <label class="form-check-label text-sm" for="env_{{ $feature->name }}_{{ $env }}">
                                                        {{ ucfirst($env) }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    @elseif($conditionType === 'schedule')
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <label class="form-label text-sm text-secondary-light mb-4">Début</label>
                                                <input type="date" class="form-control form-control-sm radius-8"
                                                    wire:model="conditionConfig.start_date">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label text-sm text-secondary-light mb-4">Fin</label>
                                                <input type="date" class="form-control form-control-sm radius-8"
                                                    wire:model="conditionConfig.end_date">
                                            </div>
                                        </div>
                                    @endif

                                    <div class="d-flex gap-8">
                                        <button type="button" class="btn btn-sm btn-success-600 radius-4 d-inline-flex align-items-center gap-1"
                                            wire:click="saveCondition">
                                            <iconify-icon icon="solar:check-circle-outline" class="icon"></iconify-icon>
                                            Enregistrer
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary radius-4"
                                            wire:click="cancelEdit">
                                            Annuler
                                        </button>
                                    </div>
                                </div>
                            @else
                                {{-- Affichage badge + bouton modifier --}}
                                <div class="d-flex align-items-center gap-8">
                                    @if($condition)
                                        <span class="badge {{ $colorMap[$currentType] ?? 'bg-neutral-200 text-neutral-600' }} d-inline-flex align-items-center gap-1" style="width:fit-content">
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
                                        <span class="text-secondary-light text-sm">Aucune condition</span>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-link p-0 text-secondary-light"
                                        wire:click="editCondition('{{ $feature->name }}')"
                                        title="Modifier les conditions">
                                        <iconify-icon icon="solar:pen-outline" class="icon text-lg"></iconify-icon>
                                    </button>
                                </div>
                            @endif
                        </td>
                        <td>
                            <form action="{{ route('admin.feature-flags.toggle', ['name' => $feature->name]) }}" method="POST" class="d-inline">
                                @csrf
                                <div class="form-check form-switch switch-primary">
                                    <input type="checkbox" class="form-check-input" role="switch"
                                        @checked($isActive)
                                        onchange="this.closest('form').submit()"
                                        title="{{ $isActive ? 'Désactiver' : 'Activer' }}">
                                </div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-secondary-light py-20">
                            <iconify-icon icon="solar:flag-2-outline" class="icon text-4xl mb-2 d-block"></iconify-icon>
                            Aucune feature flag configurée
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($features->hasPages())
        <div class="mt-20">{{ $features->links() }}</div>
    @endif
</div>
