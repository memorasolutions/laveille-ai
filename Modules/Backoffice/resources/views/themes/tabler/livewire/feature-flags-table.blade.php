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

    <div class="row mb-3 g-2">
        <div class="col-md-9">
            <div class="input-icon">
                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Rechercher un feature flag...">
            </div>
        </div>
        <div class="col-md-3">
            <button wire:click="resetFilters" class="btn btn-outline-secondary w-100">
                <i class="ti ti-x me-1"></i> Reset
            </button>
        </div>
    </div>

    {{-- Features connues non encore activées --}}
    @php
        $existingNames = $features->pluck('name')->toArray();
        $pendingFeatures = array_diff($knownFeatures ?? [], $existingNames);
    @endphp

    @if(count($pendingFeatures) > 0 && !$search)
    <div class="card mb-3 border-dashed">
        <div class="card-header py-2">
            <h6 class="mb-0 text-muted d-flex align-items-center gap-2">
                <i class="ti ti-flag me-1"></i> Features connues (non activées)
            </h6>
        </div>
        <div class="card-body py-3">
            <div class="d-flex flex-wrap gap-2">
                @foreach($pendingFeatures as $name)
                <form action="{{ route('admin.feature-flags.toggle', ['name' => $name]) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-success">
                        <i class="ti ti-plus me-1"></i>{{ $name }}
                    </button>
                </form>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <div class="table-responsive">
        <table class="table table-vcenter table-hover">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Conditions</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($features as $feature)
                @php
                    $isActive = $feature->value === 'true';
                    $condition = ($conditions ?? collect())->get($feature->name);
                    $currentType = $condition?->condition_type ?? 'always';
                    $colorMap = [
                        'always'      => 'bg-success-lt text-success',
                        'percentage'  => 'bg-azure-lt text-azure',
                        'roles'       => 'bg-warning-lt text-warning',
                        'environment' => 'bg-primary-lt text-primary',
                        'schedule'    => 'bg-danger-lt text-danger',
                    ];
                @endphp
                <tr>
                    <td>
                        <code class="fw-bold text-primary">{{ $feature->name }}</code>
                    </td>
                    <td style="min-width: 220px;">
                        @if(($editingCondition ?? null) === $feature->name)
                        <div class="d-flex flex-column gap-2">
                            <select class="form-select form-select-sm" wire:model.live="conditionType">
                                @foreach($availableTypes ?? [] as $type => $label)
                                <option value="{{ $type }}">{{ $label }}</option>
                                @endforeach
                            </select>

                            @if(($conditionType ?? 'always') === 'percentage')
                            <div>
                                <label class="form-label small text-muted mb-1">Déploiement : <strong>{{ $conditionConfig['percentage'] ?? 0 }}%</strong></label>
                                <input type="range" class="form-range" min="0" max="100" step="5" wire:model.live="conditionConfig.percentage">
                            </div>
                            @elseif(($conditionType ?? '') === 'roles')
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($roles ?? [] as $role)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="{{ $role }}" id="role_{{ $feature->name }}_{{ $role }}" wire:model="conditionConfig.roles">
                                    <label class="form-check-label small" for="role_{{ $feature->name }}_{{ $role }}">{{ $role }}</label>
                                </div>
                                @endforeach
                            </div>
                            @elseif(($conditionType ?? '') === 'environment')
                            <div class="d-flex flex-wrap gap-2">
                                @foreach(['local', 'staging', 'production'] as $env)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="{{ $env }}" id="env_{{ $feature->name }}_{{ $env }}" wire:model="conditionConfig.environments">
                                    <label class="form-check-label small" for="env_{{ $feature->name }}_{{ $env }}">{{ ucfirst($env) }}</label>
                                </div>
                                @endforeach
                            </div>
                            @elseif(($conditionType ?? '') === 'schedule')
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small text-muted mb-1">Début</label>
                                    <input type="date" class="form-control form-control-sm" wire:model="conditionConfig.start_date">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted mb-1">Fin</label>
                                    <input type="date" class="form-control form-control-sm" wire:model="conditionConfig.end_date">
                                </div>
                            </div>
                            @endif

                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-success" wire:click="saveCondition">
                                    <i class="ti ti-check me-1"></i> Enregistrer
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="cancelEdit">Annuler</button>
                            </div>
                        </div>
                        @else
                        <div class="d-flex align-items-center gap-2">
                            @if($condition)
                            <span class="badge {{ $colorMap[$currentType] ?? 'bg-secondary-lt text-muted' }}">
                                {{ ($availableTypes ?? [])[$currentType] ?? $currentType }}
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
                            <span class="text-muted small">Aucune condition</span>
                            @endif
                            <button type="button" class="btn btn-sm btn-ghost-secondary p-1"
                                wire:click="editCondition('{{ $feature->name }}')" title="Modifier les conditions">
                                <i class="ti ti-pencil"></i>
                            </button>
                        </div>
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('admin.feature-flags.toggle', ['name' => $feature->name]) }}" method="POST" class="d-inline">
                            @csrf
                            <label class="form-check form-switch mb-0">
                                <input type="checkbox" class="form-check-input"
                                    {{ $isActive ? 'checked' : '' }}
                                    onchange="this.closest('form').submit()"
                                    title="{{ $isActive ? 'Désactiver' : 'Activer' }}">
                                <span class="form-check-label small {{ $isActive ? 'text-success' : 'text-muted' }}">
                                    {{ $isActive ? 'Activé' : 'Désactivé' }}
                                </span>
                            </label>
                        </form>
                    </td>
                    <td>
                        <span class="text-muted small">—</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        <i class="ti ti-flag-off fs-2 d-block mb-2"></i>
                        Aucun feature flag trouvé
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($features->hasPages())
    <div class="d-flex align-items-center justify-content-between mt-3">
        <div class="text-muted small">{{ $features->total() }} flag(s) au total</div>
        <div>{{ $features->links() }}</div>
    </div>
    @endif
</div>
