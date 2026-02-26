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
        <div class="col-md-6">
            <div class="input-icon">
                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Rechercher un plan...">
            </div>
        </div>
        <div class="col-md-3">
            <select wire:model.live="filterInterval" class="form-select">
                <option value="">Tous les intervalles</option>
                <option value="month">Mensuel</option>
                <option value="year">Annuel</option>
                <option value="week">Hebdomadaire</option>
                <option value="day">Journalier</option>
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
                    <th style="width:40px;">#</th>
                    <th wire:click="sort('name')" style="cursor:pointer">
                        Nom <i class="ti ti-arrows-sort {{ $sortBy === 'name' ? 'text-primary' : 'text-muted' }}"></i>
                    </th>
                    <th wire:click="sort('price')" style="cursor:pointer">
                        Prix <i class="ti ti-arrows-sort {{ $sortBy === 'price' ? 'text-primary' : 'text-muted' }}"></i>
                    </th>
                    <th>Intervalle</th>
                    <th>Abonnés</th>
                    <th>Actif</th>
                    <th wire:click="sort('sort_order')" style="cursor:pointer">
                        Ordre <i class="ti ti-arrows-sort {{ $sortBy === 'sort_order' ? 'text-primary' : 'text-muted' }}"></i>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($plans as $plan)
                <tr>
                    <td class="text-muted small">{{ $plan->sort_order ?? '—' }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar avatar-sm rounded bg-primary-lt">
                                <i class="ti ti-crown text-primary" style="font-size:.85rem;"></i>
                            </span>
                            <div>
                                <div class="fw-bold">{{ $plan->name }}</div>
                                @if($plan->stripe_price_id)
                                <small class="text-muted font-monospace">{{ $plan->stripe_price_id }}</small>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($plan->price == 0)
                        <span class="badge bg-success-lt text-success fw-bold">Gratuit</span>
                        @else
                        <span class="fw-bold">{{ number_format($plan->price / 100, 2) }} {{ strtoupper($plan->currency ?? 'CAD') }}</span>
                        @endif
                    </td>
                    <td>
                        @php
                            $intervalMap = [
                                'month' => ['bg-azure-lt text-azure', 'Mensuel'],
                                'year'  => ['bg-purple-lt text-purple', 'Annuel'],
                                'week'  => ['bg-cyan-lt text-cyan', 'Hebdo'],
                                'day'   => ['bg-teal-lt text-teal', 'Journalier'],
                            ];
                            [$cls, $label] = $intervalMap[$plan->billing_interval ?? 'month'] ?? ['bg-secondary-lt', ucfirst($plan->billing_interval ?? '')];
                        @endphp
                        <span class="badge {{ $cls }}">{{ $label }}</span>
                    </td>
                    <td>
                        <span class="badge bg-secondary-lt">
                            <i class="ti ti-users me-1"></i>{{ $plan->subscriptions_count ?? 0 }}
                        </span>
                    </td>
                    <td>
                        <label class="form-check form-switch mb-0">
                            <input type="checkbox" class="form-check-input"
                                wire:click="toggleActive({{ $plan->id }})"
                                {{ $plan->is_active ? 'checked' : '' }}>
                        </label>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <button wire:click="moveUp({{ $plan->id }})" class="btn btn-sm btn-ghost-secondary" title="Monter">
                                <i class="ti ti-arrow-up"></i>
                            </button>
                            <button wire:click="moveDown({{ $plan->id }})" class="btn btn-sm btn-ghost-secondary" title="Descendre">
                                <i class="ti ti-arrow-down"></i>
                            </button>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.plans.edit', $plan) }}" class="btn btn-sm btn-outline-warning" title="Modifier">
                                <i class="ti ti-edit"></i>
                            </a>
                            <button wire:click="deletePlan({{ $plan->id }})"
                                wire:confirm="Supprimer le plan « {{ $plan->name }} » ?"
                                class="btn btn-sm btn-outline-danger" title="Supprimer">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="ti ti-crown-off fs-2 d-block mb-2"></i>
                        Aucun plan trouvé
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex align-items-center justify-content-between mt-3">
        <div class="text-muted small">{{ $plans->total() }} plan(s) au total</div>
        <div>{{ $plans->links() }}</div>
    </div>
</div>
