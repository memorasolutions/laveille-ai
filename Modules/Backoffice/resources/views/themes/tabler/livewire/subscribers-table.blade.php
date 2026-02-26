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
                <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Rechercher par courriel...">
            </div>
        </div>
        <div class="col-md-4">
            <select wire:model.live="filterStatus" class="form-select">
                <option value="">Tous les statuts</option>
                <option value="subscribed">Abonné</option>
                <option value="unsubscribed">Désabonné</option>
                <option value="pending">En attente</option>
                <option value="bounced">Rejeté</option>
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
                    <th wire:click="sort('email')" style="cursor:pointer">
                        Courriel <i class="ti ti-arrows-sort {{ ($sortBy ?? '') === 'email' ? 'text-primary' : 'text-muted' }}"></i>
                    </th>
                    <th>Nom</th>
                    <th>Statut</th>
                    <th>Source</th>
                    <th wire:click="sort('subscribed_at')" style="cursor:pointer">
                        Abonné le <i class="ti ti-arrows-sort {{ ($sortBy ?? '') === 'subscribed_at' ? 'text-primary' : 'text-muted' }}"></i>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscribers as $subscriber)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar avatar-xs rounded-circle bg-primary-lt"
                                style="font-size:.65rem; width:24px; height:24px; display:inline-flex; align-items:center; justify-content:center;">
                                {{ strtoupper(substr($subscriber->email, 0, 1)) }}
                            </span>
                            <span class="small fw-medium">{{ $subscriber->email }}</span>
                        </div>
                    </td>
                    <td class="small text-muted">
                        {{ $subscriber->first_name ? $subscriber->first_name . ' ' . $subscriber->last_name : '—' }}
                    </td>
                    <td>
                        @php
                            $statusMap = [
                                'subscribed'   => ['bg-success-lt text-success', 'ti-circle-check', 'Abonné'],
                                'unsubscribed' => ['bg-secondary-lt text-secondary', 'ti-circle-minus', 'Désabonné'],
                                'pending'      => ['bg-warning-lt text-warning', 'ti-clock', 'En attente'],
                                'bounced'      => ['bg-danger-lt text-danger', 'ti-alert-circle', 'Rejeté'],
                            ];
                            [$cls, $icon, $label] = $statusMap[$subscriber->status] ?? ['bg-secondary-lt', 'ti-question-mark', ucfirst($subscriber->status ?? '')];
                        @endphp
                        <span class="badge {{ $cls }}">
                            <i class="ti {{ $icon }} me-1"></i>{{ $label }}
                        </span>
                    </td>
                    <td>
                        <span class="text-muted small">{{ $subscriber->source ?? '—' }}</span>
                    </td>
                    <td class="text-muted small">
                        {{ $subscriber->subscribed_at ? $subscriber->subscribed_at->format('d/m/Y') : ($subscriber->created_at->format('d/m/Y')) }}
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            @if($subscriber->status === 'subscribed')
                            <button wire:click="unsubscribe({{ $subscriber->id }})"
                                wire:confirm="Désabonner {{ $subscriber->email }} ?"
                                class="btn btn-sm btn-outline-warning" title="Désabonner">
                                <i class="ti ti-mail-off"></i>
                            </button>
                            @else
                            <button wire:click="resubscribe({{ $subscriber->id }})"
                                wire:confirm="Réabonner {{ $subscriber->email }} ?"
                                class="btn btn-sm btn-outline-success" title="Réabonner">
                                <i class="ti ti-mail"></i>
                            </button>
                            @endif
                            <button wire:click="deleteSubscriber({{ $subscriber->id }})"
                                wire:confirm="Supprimer définitivement cet abonné ?"
                                class="btn btn-sm btn-outline-danger" title="Supprimer">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="ti ti-mail-off fs-2 d-block mb-2"></i>
                        Aucun abonné trouvé
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex align-items-center justify-content-between mt-3">
        <div class="text-muted small">{{ $subscribers->total() }} abonné(s) au total</div>
        <div>{{ $subscribers->links() }}</div>
    </div>
</div>
