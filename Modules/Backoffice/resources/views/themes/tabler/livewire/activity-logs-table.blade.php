<div>
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <div class="d-flex"><i class="ti ti-check me-2"></i>{{ session('success') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row mb-3 g-2">
        <div class="col-md-4">
            <div class="input-icon">
                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Rechercher dans les logs...">
            </div>
        </div>
        <div class="col-md-3">
            <select wire:model.live="filterEvent" class="form-select">
                <option value="">Tous les événements</option>
                <option value="created">Créé</option>
                <option value="updated">Modifié</option>
                <option value="deleted">Supprimé</option>
                <option value="login">Connexion</option>
                <option value="logout">Déconnexion</option>
                <option value="restored">Restauré</option>
            </select>
        </div>
        <div class="col-md-3">
            <select wire:model.live="filterSubjectType" class="form-select">
                <option value="">Tous les modèles</option>
                @foreach($availableSubjectTypes ?? [] as $type => $label)
                <option value="{{ $type }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button wire:click="resetFilters" class="btn btn-outline-secondary w-100">
                <i class="ti ti-x me-1"></i> Reset
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter table-hover">
            <thead>
                <tr>
                    <th>Événement</th>
                    <th wire:click="sort('description')" style="cursor:pointer">
                        Description <i class="ti ti-arrows-sort {{ ($sortBy ?? '') === 'description' ? 'text-primary' : 'text-muted' }}"></i>
                    </th>
                    <th>Utilisateur</th>
                    <th>Modèle</th>
                    <th wire:click="sort('created_at')" style="cursor:pointer">
                        Date <i class="ti ti-arrows-sort {{ ($sortBy ?? '') === 'created_at' ? 'text-primary' : 'text-muted' }}"></i>
                    </th>
                    <th>Détails</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $log)
                <tr>
                    <td>
                        @php
                            $eventMap = [
                                'created'  => ['bg-success-lt text-success', 'ti-plus', 'Créé'],
                                'updated'  => ['bg-warning-lt text-warning', 'ti-pencil', 'Modifié'],
                                'deleted'  => ['bg-danger-lt text-danger', 'ti-trash', 'Supprimé'],
                                'login'    => ['bg-azure-lt text-azure', 'ti-login', 'Connexion'],
                                'logout'   => ['bg-secondary-lt text-secondary', 'ti-logout', 'Déconnexion'],
                                'restored' => ['bg-teal-lt text-teal', 'ti-restore', 'Restauré'],
                            ];
                            [$cls, $icon, $label] = $eventMap[$log->event ?? ''] ?? ['bg-secondary-lt text-muted', 'ti-activity', ucfirst($log->event ?? 'action')];
                        @endphp
                        <span class="badge {{ $cls }}">
                            <i class="ti {{ $icon }} me-1"></i>{{ $label }}
                        </span>
                    </td>
                    <td style="max-width: 240px;">
                        <div class="text-truncate small" title="{{ $log->description }}">
                            {{ $log->description }}
                        </div>
                    </td>
                    <td>
                        @if($log->causer)
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar avatar-xs rounded-circle bg-primary-lt"
                                style="font-size:.65rem; width:22px; height:22px; display:inline-flex; align-items:center; justify-content:center;">
                                {{ strtoupper(substr($log->causer->name ?? 'S', 0, 1)) }}
                            </span>
                            <div>
                                <div class="small fw-medium">{{ $log->causer->name ?? '—' }}</div>
                                @if($log->properties['ip'] ?? null)
                                <small class="text-muted font-monospace">{{ $log->properties['ip'] }}</small>
                                @endif
                            </div>
                        </div>
                        @else
                        <span class="text-muted small">Système</span>
                        @endif
                    </td>
                    <td>
                        @if($log->subject_type)
                        @php
                            $shortType = class_basename($log->subject_type);
                        @endphp
                        <div>
                            <span class="badge bg-secondary-lt small">{{ $shortType }}</span>
                            @if($log->subject_id)
                            <small class="text-muted ms-1">#{{ $log->subject_id }}</small>
                            @endif
                        </div>
                        @else
                        <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td class="text-muted small" title="{{ $log->created_at->format('d/m/Y H:i:s') }}">
                        {{ $log->created_at->diffForHumans() }}
                    </td>
                    <td>
                        @if($log->properties && count($log->properties) > 0)
                        <button type="button"
                            class="btn btn-sm btn-ghost-secondary"
                            data-bs-toggle="modal"
                            data-bs-target="#logModal{{ $log->id }}"
                            title="Voir les détails">
                            <i class="ti ti-list-details"></i>
                        </button>

                        {{-- Modal détails --}}
                        <div class="modal modal-blur fade" id="logModal{{ $log->id }}" tabindex="-1" role="dialog">
                            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            <i class="ti ti-activity me-2"></i>
                                            Détails du log #{{ $log->id }}
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row g-3 mb-3">
                                            <div class="col-6">
                                                <div class="text-muted small">Événement</div>
                                                <div class="fw-bold">{{ $log->event ?? '—' }}</div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-muted small">Date</div>
                                                <div class="fw-bold">{{ $log->created_at->format('d/m/Y H:i:s') }}</div>
                                            </div>
                                        </div>
                                        @if($log->properties->get('old') || $log->properties->get('attributes'))
                                        <div class="row g-3">
                                            @if($log->properties->get('old'))
                                            <div class="col-md-6">
                                                <div class="text-muted small mb-2 fw-bold text-uppercase">Avant</div>
                                                <pre class="bg-danger-lt rounded p-2 small" style="max-height:200px; overflow-y:auto;">{{ json_encode($log->properties->get('old'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            </div>
                                            @endif
                                            @if($log->properties->get('attributes'))
                                            <div class="col-md-6">
                                                <div class="text-muted small mb-2 fw-bold text-uppercase">Après</div>
                                                <pre class="bg-success-lt rounded p-2 small" style="max-height:200px; overflow-y:auto;">{{ json_encode($log->properties->get('attributes'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            </div>
                                            @endif
                                        </div>
                                        @else
                                        <pre class="bg-light rounded p-2 small">{{ json_encode($log->properties->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        @endif
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @else
                        <span class="text-muted small">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="ti ti-activity-off fs-2 d-block mb-2"></i>
                        Aucun log d'activité trouvé
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex align-items-center justify-content-between mt-3">
        <div class="text-muted small">{{ $activities->total() }} log(s) au total</div>
        <div>{{ $activities->links() }}</div>
    </div>
</div>
