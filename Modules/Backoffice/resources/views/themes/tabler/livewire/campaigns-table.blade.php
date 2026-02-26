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
        <div class="col-md-5">
            <div class="input-icon">
                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Rechercher une campagne...">
            </div>
        </div>
        <div class="col-md-4">
            <select wire:model.live="filterStatus" class="form-select">
                <option value="">Tous les statuts</option>
                <option value="draft">Brouillon</option>
                <option value="scheduled">Planifiée</option>
                <option value="sending">En cours</option>
                <option value="sent">Envoyée</option>
                <option value="failed">Échouée</option>
            </select>
        </div>
        <div class="col-md-3">
            <button wire:click="resetFilters" class="btn btn-outline-secondary w-100">
                <i class="ti ti-x me-1"></i> Reset
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter table-hover">
            <thead>
                <tr>
                    <th wire:click="sort('subject')" style="cursor:pointer">
                        Sujet <i class="ti ti-arrows-sort {{ ($sortBy ?? '') === 'subject' ? 'text-primary' : 'text-muted' }}"></i>
                    </th>
                    <th>Statut</th>
                    <th>Destinataires</th>
                    <th>Ouverts</th>
                    <th wire:click="sort('sent_at')" style="cursor:pointer">
                        Envoyé le <i class="ti ti-arrows-sort {{ ($sortBy ?? '') === 'sent_at' ? 'text-primary' : 'text-muted' }}"></i>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($campaigns as $campaign)
                <tr>
                    <td style="max-width: 260px;">
                        <div class="text-truncate fw-medium" title="{{ $campaign->subject }}">
                            {{ $campaign->subject }}
                        </div>
                    </td>
                    <td>
                        @php
                            $statusMap = [
                                'draft'     => ['bg-secondary-lt text-secondary', 'ti-pencil', 'Brouillon'],
                                'scheduled' => ['bg-warning-lt text-warning', 'ti-clock', 'Planifiée'],
                                'sending'   => ['bg-azure-lt text-azure', 'ti-loader', 'En cours'],
                                'sent'      => ['bg-success-lt text-success', 'ti-circle-check', 'Envoyée'],
                                'failed'    => ['bg-danger-lt text-danger', 'ti-alert-circle', 'Échouée'],
                            ];
                            [$cls, $icon, $label] = $statusMap[$campaign->status] ?? ['bg-secondary-lt', 'ti-question-mark', ucfirst($campaign->status ?? '')];
                        @endphp
                        <span class="badge {{ $cls }}">
                            <i class="ti {{ $icon }} me-1"></i>{{ $label }}
                        </span>
                    </td>
                    <td>
                        <span class="text-muted">
                            <i class="ti ti-users me-1"></i>{{ number_format($campaign->recipients_count ?? 0) }}
                        </span>
                    </td>
                    <td>
                        @if(($campaign->recipients_count ?? 0) > 0)
                        @php $rate = round(($campaign->opened_count ?? 0) / $campaign->recipients_count * 100); @endphp
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress" style="width:60px; height:6px;">
                                <div class="progress-bar bg-success" style="width:{{ $rate }}%"></div>
                            </div>
                            <small>{{ $rate }}%</small>
                        </div>
                        @else
                        <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td class="text-muted small">
                        {{ $campaign->sent_at ? $campaign->sent_at->format('d/m/Y H:i') : '—' }}
                    </td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-ghost-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="ti ti-dots-vertical"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('admin.campaigns.show', $campaign) }}">
                                    <i class="ti ti-eye me-2"></i> Voir
                                </a>
                                @if(in_array($campaign->status, ['draft', 'scheduled']))
                                <a class="dropdown-item" href="{{ route('admin.campaigns.edit', $campaign) }}">
                                    <i class="ti ti-edit me-2"></i> Modifier
                                </a>
                                <button wire:click="sendCampaign({{ $campaign->id }})"
                                    wire:confirm="Envoyer cette campagne maintenant ?"
                                    class="dropdown-item text-success">
                                    <i class="ti ti-send me-2"></i> Envoyer
                                </button>
                                @endif
                                <div class="dropdown-divider"></div>
                                <button wire:click="deleteCampaign({{ $campaign->id }})"
                                    wire:confirm="Supprimer cette campagne ?"
                                    class="dropdown-item text-danger">
                                    <i class="ti ti-trash me-2"></i> Supprimer
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="ti ti-mail-off fs-2 d-block mb-2"></i>
                        Aucune campagne trouvée
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex align-items-center justify-content-between mt-3">
        <div class="text-muted small">{{ $campaigns->total() }} campagne(s) au total</div>
        <div>{{ $campaigns->links() }}</div>
    </div>
</div>
