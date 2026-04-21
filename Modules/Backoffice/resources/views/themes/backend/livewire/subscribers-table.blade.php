<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border rounded-3 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="small fw-medium text-muted mb-1">{{ __('Total inscrits') }}</p>
                            <h6 class="fs-4 fw-bold text-body mb-0">{{ $totalCount }}</h6>
                        </div>
                        <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary" style="width:48px;height:48px;flex-shrink:0;">
                            <i data-lucide="users" style="width:22px;height:22px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border rounded-3 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="small fw-medium text-muted mb-1">{{ __('Actifs (confirmés)') }}</p>
                            <h6 class="fs-4 fw-bold text-body mb-0">{{ $activeCount }}</h6>
                        </div>
                        <div class="d-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 text-success" style="width:48px;height:48px;flex-shrink:0;">
                            <i data-lucide="check-circle" style="width:22px;height:22px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border rounded-3 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="small fw-medium text-muted mb-1">{{ __('En attente') }}</p>
                            <h6 class="fs-4 fw-bold text-body mb-0">{{ $pendingCount }}</h6>
                        </div>
                        <div class="d-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-10 text-warning" style="width:48px;height:48px;flex-shrink:0;">
                            <i data-lucide="clock" style="width:22px;height:22px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border rounded-3 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="small fw-medium text-muted mb-1">{{ __('Désabonnés') }}</p>
                            <h6 class="fs-4 fw-bold text-body mb-0">{{ $unsubscribedCount }}</h6>
                        </div>
                        <div class="d-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10 text-danger" style="width:48px;height:48px;flex-shrink:0;">
                            <i data-lucide="x-circle" style="width:22px;height:22px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2 mb-3" role="alert">
            <i data-lucide="check-circle" style="width:16px;height:16px;flex-shrink:0;"></i>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert">
            <i data-lucide="alert-triangle" style="width:16px;height:16px;flex-shrink:0;"></i>
            {{ session('error') }}
        </div>
    @endif

    @if(count($selected) > 0)
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3 px-3 py-2 bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded">
            <span class="small fw-medium text-body">{{ count($selected) }} {{ __('sélectionné(s)') }}</span>
            <select wire:model.live="bulkAction" class="form-select form-select-sm w-auto" aria-label="Action groupée">
                <option value="">{{ __('Choisir une action') }}</option>
                <option value="delete">{{ __('Supprimer') }}</option>
            </select>
            <button wire:click="executeBulkAction" wire:confirm="{{ __('Confirmer l\'action en masse ?') }}"
                    class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
                <i data-lucide="play-circle" style="width:14px;height:14px;"></i> {{ __('Exécuter') }}
            </button>
        </div>
    @endif

    {{-- Filtres --}}
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <div class="position-relative">
            <input type="text" wire:model.live.debounce.300ms="search"
                   class="form-control form-control-sm ps-4"
                   placeholder="{{ __('Rechercher par email ou nom...') }}"
                   aria-label="{{ __('Rechercher') }}">
            <i data-lucide="search" class="position-absolute" style="left:10px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:#6b7280;"></i>
        </div>
        <select wire:model.live="filterStatus" class="form-select form-select-sm w-auto" aria-label="{{ __('Filtrer par statut') }}">
            <option value="">{{ __('Tous les statuts') }}</option>
            <option value="active">{{ __('Actif') }}</option>
            <option value="pending">{{ __('En attente') }}</option>
            <option value="unsubscribed">{{ __('Désabonné') }}</option>
        </select>
        <button wire:click="resetFilters"
                class="btn btn-sm btn-light d-inline-flex align-items-center gap-1">
            <i data-lucide="x-circle" style="width:14px;height:14px;"></i> {{ __('Réinitialiser') }}
        </button>
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr class="border-bottom">
                    <th style="width:40px;">
                        <input type="checkbox" wire:model.live="selectAll" class="form-check-input cursor-pointer" style="width:16px;height:16px;" aria-label="Tout sélectionner">
                    </th>
                    <th class="fw-medium">{{ __('Email') }}</th>
                    <th class="fw-medium">{{ __('Nom') }}</th>
                    <th class="fw-medium">{{ __('Statut') }}</th>
                    <th class="fw-medium">{{ __('Confirmé le') }}</th>
                    <th class="fw-medium">{{ __('Inscrit le') }}</th>
                    <th class="fw-medium">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscribers as $sub)
                <tr>
                    <td>
                        <input type="checkbox" wire:model.live="selected" value="{{ $sub->id }}" class="form-check-input cursor-pointer" style="width:16px;height:16px;" aria-label="Sélectionner">
                    </td>
                    <td class="small text-body">{{ $sub->email }}</td>
                    <td class="small text-muted">{{ $sub->name ?? '–' }}</td>
                    <td>
                        @if($sub->isActive())
                            <span class="badge rounded py-1 px-2 fw-medium small bg-success bg-opacity-10 text-success border border-success border-opacity-25">{{ __('Actif') }}</span>
                        @elseif($sub->unsubscribed_at)
                            <span class="badge rounded py-1 px-2 fw-medium small bg-light text-muted border">{{ __('Désabonné') }}</span>
                        @else
                            <span class="badge rounded py-1 px-2 fw-medium small bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">{{ __('En attente') }}</span>
                        @endif
                    </td>
                    <td class="text-muted small">{{ $sub->confirmed_at?->format('d/m/Y') ?? '–' }}</td>
                    <td class="text-muted small">{{ $sub->created_at->format('d/m/Y') }}</td>
                    <td>
                        <div class="position-relative d-inline-block" x-data="{ open: false }" @click.outside="open = false">
                            <button @click="open = !open"
                                    class="btn btn-sm btn-light d-flex align-items-center justify-content-center rounded-circle"
                                    style="width:32px;height:32px;padding:0;">
                                <i data-lucide="more-horizontal" style="width:16px;height:16px;"></i>
                            </button>
                            <div x-show="open" x-cloak
                                 class="position-absolute end-0 bg-white border rounded shadow py-1"
                                 style="top:100%;margin-top:4px;min-width:140px;z-index:50;">
                                <button wire:click="delete({{ $sub->id }})"
                                        wire:confirm="{{ __('Supprimer cet abonné ?') }}"
                                        class="btn btn-link w-100 d-flex align-items-center gap-2 px-3 py-2 small text-danger text-decoration-none text-start">
                                    <i data-lucide="trash-2" style="width:14px;height:14px;"></i> {{ __('Supprimer') }}
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-5 text-center text-muted small">
                        <i data-lucide="mail" class="d-block mx-auto mb-2 text-muted" style="width:32px;height:32px;opacity:.4;"></i>
                        {{ __('Aucun abonné') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3 pt-3 border-top">
        <span class="text-muted small">{{ $subscribers->total() }} {{ __('abonné(s)') }}</span>
        {{ $subscribers->links() }}
    </div>
</div>
