<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
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
                <option value="delete">{{ __('Supprimer') }}</option>
            </select>
            <button wire:click="executeBulkAction" wire:confirm="{{ __('Confirmer l\'action en masse ?') }}"
                    class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
                <i data-lucide="play-circle" class="icon-sm"></i> {{ __('Exécuter') }}
            </button>
        </div>
    @endif

    {{-- Filtres --}}
    <div class="border-bottom pb-3 mb-3">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <div class="input-group input-group-sm w-auto">
                <span class="input-group-text bg-white border-end-0">
                    <i data-lucide="search" class="icon-sm text-muted"></i>
                </span>
                <input type="text" wire:model.live.debounce.300ms="search"
                       class="form-control form-control-sm border-start-0"
                       placeholder="{{ __('Rechercher une campagne...') }}"
                       aria-label="Rechercher">
            </div>
            <select wire:model.live="filterStatus" class="form-select form-select-sm w-auto" aria-label="Filtrer par statut">
                <option value="">{{ __('Tous les statuts') }}</option>
                <option value="draft">{{ __('Brouillon') }}</option>
                <option value="sent">{{ __('Envoyé') }}</option>
            </select>
            <button wire:click="resetFilters"
                    class="btn btn-sm btn-light d-inline-flex align-items-center gap-1">
                <i data-lucide="refresh-cw" class="icon-sm"></i> {{ __('Réinitialiser') }}
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr class="border-bottom">
                    <th style="width:40px;">
                        <input type="checkbox" wire:model.live="selectAll" class="form-check-input" style="width:16px;height:16px;" aria-label="Tout sélectionner">
                    </th>
                    <th class="fw-medium">{{ __('Sujet') }}</th>
                    <th class="fw-medium">{{ __('Statut') }}</th>
                    <th class="fw-medium">{{ __('Destinataires') }}</th>
                    <th class="fw-medium">{{ __('Envoyé le') }}</th>
                    <th class="fw-medium">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($campaigns as $campaign)
                <tr>
                    <td>
                        <input type="checkbox" wire:model.live="selected" value="{{ $campaign->id }}" class="form-check-input" style="width:16px;height:16px;" aria-label="Sélectionner">
                    </td>
                    <td class="text-body">{{ $campaign->subject }}</td>
                    <td>
                        <select wire:change="changeStatus({{ $campaign->id }}, $event.target.value)"
                                class="form-select form-select-sm w-auto"
                                aria-label="Changer le statut"
                                @if($campaign->isSent()) disabled @endif>
                            <option value="draft" @selected($campaign->status === 'draft')>{{ __('Brouillon') }}</option>
                            <option value="sent" @selected($campaign->status === 'sent')>{{ __('Envoyé') }}</option>
                        </select>
                    </td>
                    <td class="text-muted">{{ $campaign->recipient_count }}</td>
                    <td class="text-muted">{{ $campaign->sent_at?->format('d/m/Y H:i') ?? '–' }}</td>
                    <td>
                        <div class="position-relative d-inline-block" x-data="{ open: false }" @click.outside="open = false">
                            <button @click="open = !open"
                                    class="btn btn-sm btn-light rounded-circle d-inline-flex align-items-center justify-content-center"
                                    style="width:32px;height:32px;">
                                <i data-lucide="more-vertical" class="icon-sm"></i>
                            </button>
                            <div x-show="open" x-cloak
                                 class="dropdown-menu show position-absolute end-0 mt-1 shadow"
                                 style="z-index:50;min-width:140px;">
                                @if($campaign->isDraft())
                                    <form method="POST" action="{{ route('admin.newsletter.campaigns.send', $campaign) }}"
                                          onsubmit="return confirm('{{ __('Envoyer cette campagne à tous les abonnés actifs ?') }}')">
                                        @csrf
                                        <button type="submit"
                                                class="dropdown-item d-flex align-items-center gap-2 text-success">
                                            <i data-lucide="send" class="icon-sm"></i> {{ __('Envoyer') }}
                                        </button>
                                    </form>
                                @else
                                    <span class="dropdown-item d-flex align-items-center gap-2 text-muted" style="cursor:not-allowed;opacity:.5;">
                                        <i data-lucide="send" class="icon-sm"></i> {{ __('Déjà envoyée') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-5 text-center text-muted">
                        <i data-lucide="mail" class="d-block mx-auto mb-2 text-muted" style="width:32px;height:32px;opacity:.4;"></i>
                        {{ __('Aucune campagne') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3 pt-3 border-top">
        <span class="text-muted small">{{ $campaigns->total() }} {{ __('campagne(s)') }}</span>
        {{ $campaigns->links() }}
    </div>
</div>
