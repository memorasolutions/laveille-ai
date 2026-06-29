{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
<div>
    {{-- Onglets de filtre rapide : boîte légitime / non lus / spam --}}
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item">
            <button wire:click="$set('filterStatus', '')"
                    class="nav-link {{ $filterStatus === '' ? 'active' : '' }}"
                    role="tab"
                    aria-selected="{{ $filterStatus === '' ? 'true' : 'false' }}">
                <i data-lucide="inbox" class="icon-sm"></i> {{ __('Boîte') }}
            </button>
        </li>
        <li class="nav-item">
            <button wire:click="$set('filterStatus', 'new')"
                    class="nav-link {{ $filterStatus === 'new' ? 'active' : '' }}"
                    role="tab"
                    aria-selected="{{ $filterStatus === 'new' ? 'true' : 'false' }}">
                <i data-lucide="mail" class="icon-sm"></i> {{ __('Non lus') }}
                @if($unreadCount > 0)
                    <span class="badge bg-danger ms-1">{{ $unreadCount }}</span>
                @endif
            </button>
        </li>
        <li class="nav-item">
            <button wire:click="$set('filterStatus', 'spam')"
                    class="nav-link {{ $filterStatus === 'spam' ? 'active' : '' }}"
                    role="tab"
                    aria-selected="{{ $filterStatus === 'spam' ? 'true' : 'false' }}">
                <i data-lucide="shield-alert" class="icon-sm"></i> {{ __('Spam') }}
                @if($spamCount > 0)
                    <span class="badge bg-warning text-dark ms-1">{{ $spamCount }}</span>
                @endif
            </button>
        </li>
    </ul>

    @if($filterStatus === 'spam')
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-3" role="alert">
        <i data-lucide="info" class="icon-sm"></i>
        <span>{{ __('Messages mis en quarantaine par le filtre anti-pourriel. Vérifiez l\'absence de faux positif : si un message est légitime, cliquez « Marquer comme légitime » pour le replacer dans la boîte.') }}</span>
    </div>
    @endif

    {{-- Filtres --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="d-flex gap-3 align-items-center flex-wrap">
                <div class="d-flex align-items-center gap-2">
                    <label class="form-label mb-0 small text-nowrap">{{ __('Statut :') }}</label>
                    <select wire:model.live="filterStatus" class="form-select form-select-sm" style="width:auto">
                        <option value="">{{ __('Boîte (légitimes)') }}</option>
                        <option value="new">{{ __('Non lus') }}</option>
                        <option value="read">{{ __('Lus') }}</option>
                        <option value="spam">{{ __('Spam') }}</option>
                    </select>
                </div>
                <div class="d-flex align-items-center gap-2 flex-grow-1">
                    <label class="form-label mb-0 small text-nowrap">{{ __('Recherche :') }}</label>
                    <input type="text"
                           wire:model.live.debounce.400ms="search"
                           class="form-control form-control-sm"
                           placeholder="{{ __('Nom, email ou sujet...') }}"
                           aria-label="{{ __('Rechercher') }}">
                </div>
                @if($filterStatus !== '' || $search !== '')
                <button wire:click="resetFilters" type="button" class="btn btn-sm btn-outline-secondary">
                    <i data-lucide="x"></i> {{ __('Réinitialiser') }}
                </button>
                @endif
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if($messages->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width:30px"></th>
                            <th>{{ __('De') }}</th>
                            <th>{{ __('Sujet') }}</th>
                            @if($filterStatus === 'spam')
                            <th>{{ __('Raison') }}</th>
                            @endif
                            <th>{{ __('Date') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($messages as $msg)
                        <tr class="{{ $msg->isNew() ? 'fw-bold' : '' }}">
                            <td>
                                @if($msg->isNew())
                                    <span class="badge bg-primary rounded-circle p-1">&nbsp;</span>
                                @endif
                            </td>
                            <td>
                                <div>{{ $msg->name }}</div>
                                <small class="text-muted">{{ $msg->email }}</small>
                            </td>
                            <td>{{ Str::limit($msg->subject, 60) }}</td>
                            @if($filterStatus === 'spam')
                            <td>
                                @if($msg->spam_reason)
                                    <span class="badge bg-warning text-dark" title="{{ __('Signaux anti-pourriel déclenchés') }}">{{ $msg->spam_reason }}</span>
                                @else
                                    <span class="text-muted small">{{ __('non précisée') }}</span>
                                @endif
                            </td>
                            @endif
                            <td>
                                <span title="{{ $msg->created_at->format('d/m/Y H:i') }}">
                                    {{ $msg->created_at->diffForHumans() }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.contact-messages.show', $msg) }}"
                                   class="btn btn-sm btn-outline-primary"
                                   title="{{ __('Voir') }}">
                                    <i data-lucide="eye"></i>
                                </a>
                                @if($msg->isSpam())
                                <button
                                    wire:click="markLegit({{ $msg->id }})"
                                    wire:confirm="{{ __('Marquer ce message comme légitime ?') }}"
                                    type="button"
                                    class="btn btn-sm btn-outline-success ms-1"
                                    title="{{ __('Marquer comme légitime') }}"
                                >
                                    <i data-lucide="shield-check"></i>
                                </button>
                                @endif
                                @can('delete_contacts')
                                <button
                                    wire:click="deleteMessage({{ $msg->id }})"
                                    wire:confirm="{{ __('Supprimer ce message ?') }}"
                                    type="button"
                                    class="btn btn-sm btn-outline-danger ms-1"
                                    title="{{ __('Supprimer') }}"
                                >
                                    <i data-lucide="trash-2"></i>
                                </button>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                @include('backoffice::partials.infinite-scroll', ['paginator' => $messages])
            </div>
            @else
            <div class="text-center py-5">
                <i data-lucide="mail" class="icon-xl text-muted mb-3"></i>
                <h5 class="text-muted">{{ __('Aucun message') }}</h5>
                <p class="text-muted">{{ __('Les messages envoyés via le formulaire de contact apparaîtront ici.') }}</p>
            </div>
            @endif
        </div>
    </div>
</div>
