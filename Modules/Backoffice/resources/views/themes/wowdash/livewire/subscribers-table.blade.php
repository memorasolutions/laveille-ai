<div>
    {{-- Stats --}}
    <div class="row gy-4 mb-24">
        <div class="col-md-3 col-sm-6">
            <div class="card shadow-none border bg-gradient-start-1 h-100">
                <div class="card-body p-20">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <p class="fw-medium text-primary-light mb-1">Total inscrits</p>
                            <h6 class="mb-0">{{ $totalCount }}</h6>
                        </div>
                        <div class="w-50-px h-50-px bg-cyan rounded-circle d-flex justify-content-center align-items-center">
                            <iconify-icon icon="solar:users-group-rounded-outline" class="text-white text-2xl mb-0"></iconify-icon>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card shadow-none border bg-gradient-start-2 h-100">
                <div class="card-body p-20">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <p class="fw-medium text-primary-light mb-1">Actifs (confirmés)</p>
                            <h6 class="mb-0">{{ $activeCount }}</h6>
                        </div>
                        <div class="w-50-px h-50-px bg-success-main rounded-circle d-flex justify-content-center align-items-center">
                            <iconify-icon icon="solar:check-circle-outline" class="text-white text-2xl mb-0"></iconify-icon>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card shadow-none border bg-gradient-start-3 h-100">
                <div class="card-body p-20">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <p class="fw-medium text-primary-light mb-1">En attente</p>
                            <h6 class="mb-0">{{ $pendingCount }}</h6>
                        </div>
                        <div class="w-50-px h-50-px bg-warning-main rounded-circle d-flex justify-content-center align-items-center">
                            <iconify-icon icon="solar:clock-circle-outline" class="text-white text-2xl mb-0"></iconify-icon>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card shadow-none border bg-gradient-start-4 h-100">
                <div class="card-body p-20">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <p class="fw-medium text-primary-light mb-1">Désabonnés</p>
                            <h6 class="mb-0">{{ $unsubscribedCount }}</h6>
                        </div>
                        <div class="w-50-px h-50-px bg-danger-main rounded-circle d-flex justify-content-center align-items-center">
                            <iconify-icon icon="solar:close-circle-outline" class="text-white text-2xl mb-0"></iconify-icon>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
            <input type="text" wire:model.live.debounce.300ms="search" class="bg-base h-40-px w-auto" placeholder="Rechercher par email ou nom...">
            <iconify-icon icon="ion:search-outline" class="icon"></iconify-icon>
        </form>
        <select wire:model.live="filterStatus" class="form-select form-select-sm w-auto ps-12 py-6 radius-12 h-40-px">
            <option value="">Tous les statuts</option>
            <option value="active">Actif</option>
            <option value="pending">En attente</option>
            <option value="unsubscribed">Désabonné</option>
        </select>
        <button wire:click="resetFilters" class="btn btn-sm text-neutral-600 bg-neutral-100 bg-hover-neutral-200 radius-4 d-inline-flex align-items-center gap-1">
            <iconify-icon icon="solar:close-circle-outline" class="icon"></iconify-icon> Réinitialiser
        </button>
    </div>

    {{-- Table --}}
    <div class="table-responsive scroll-sm">
        <table class="table bordered-table sm-table mb-0">
            <thead>
                <tr>
                    <th style="width:40px"><input type="checkbox" wire:model.live="selectAll" class="form-check-input"></th>
                    <th>Email</th>
                    <th>Nom</th>
                    <th>Statut</th>
                    <th>Confirmé le</th>
                    <th>Inscrit le</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscribers as $sub)
                <tr>
                    <td><input type="checkbox" wire:model.live="selected" value="{{ $sub->id }}" class="form-check-input"></td>
                    <td>{{ $sub->email }}</td>
                    <td>{{ $sub->name ?? '–' }}</td>
                    <td>
                        @if($sub->isActive())
                            <span class="bg-success-focus text-success-600 border border-success-main px-24 py-4 radius-4 fw-medium text-sm">Actif</span>
                        @elseif($sub->unsubscribed_at)
                            <span class="bg-neutral-200 text-neutral-600 border border-neutral-400 px-24 py-4 radius-4 fw-medium text-sm">Désabonné</span>
                        @else
                            <span class="bg-warning-focus text-warning-600 border border-warning-main px-24 py-4 radius-4 fw-medium text-sm">En attente</span>
                        @endif
                    </td>
                    <td>{{ $sub->confirmed_at?->format('d/m/Y') ?? '–' }}</td>
                    <td>{{ $sub->created_at->format('d/m/Y') }}</td>
                    <td>
                        <div class="dropdown">
                            <button type="button" class="w-40-px h-40-px bg-neutral-200 text-secondary-light rounded-circle d-flex justify-content-center align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
                                <iconify-icon icon="tabler:dots-vertical" class="icon text-xl"></iconify-icon>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-12">
                                <button wire:click="delete({{ $sub->id }})" wire:confirm="Supprimer cet abonné ?" class="dropdown-item d-flex align-items-center gap-2 px-12 py-8 rounded-4 text-sm text-danger-600">
                                    <iconify-icon icon="fluent:delete-24-regular" class="icon text-lg"></iconify-icon> Supprimer
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-neutral-600 py-32">
                        <iconify-icon icon="solar:letter-outline" class="icon text-4xl mb-2 d-block"></iconify-icon>
                        Aucun abonné
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span class="text-neutral-600 text-sm">{{ $subscribers->total() }} abonné(s)</span>
        {{ $subscribers->links() }}
    </div>
</div>
