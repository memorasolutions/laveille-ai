<div>
    {{-- Filtres --}}
    <div class="card-body border-bottom pb-16">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <form class="navbar-search">
                <input type="text" wire:model.live.debounce.300ms="search" class="bg-base h-40-px w-auto" placeholder="{{ __('Rechercher...') }}">
                <iconify-icon icon="ion:search-outline" class="icon"></iconify-icon>
            </form>
            <select wire:model.live="filterCauser" class="form-select form-select-sm w-auto ps-12 py-6 radius-12 h-40-px">
                <option value="">{{ __('Tous les utilisateurs') }}</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="filterLogName" class="form-select form-select-sm w-auto ps-12 py-6 radius-12 h-40-px">
                <option value="">{{ __('Tous les journaux') }}</option>
                @foreach($logNames as $logName)
                    <option value="{{ $logName }}">{{ $logName }}</option>
                @endforeach
            </select>
            <select wire:model.live="filterEvent" class="form-select form-select-sm w-auto ps-12 py-6 radius-12 h-40-px">
                <option value="">{{ __('Tous les événements') }}</option>
                @foreach($events as $event)
                    <option value="{{ $event }}">{{ ucfirst($event) }}</option>
                @endforeach
            </select>
            <input type="date" wire:model.live="dateFrom" class="form-control form-control-sm w-auto h-40-px radius-12" title="{{ __('Date début') }}">
            <input type="date" wire:model.live="dateTo" class="form-control form-control-sm w-auto h-40-px radius-12" title="{{ __('Date fin') }}">
            <button wire:click="resetFilters" class="btn btn-sm text-neutral-600 bg-neutral-100 bg-hover-neutral-200 radius-4 d-inline-flex align-items-center gap-1">
                <iconify-icon icon="solar:close-circle-outline" class="icon"></iconify-icon> {{ __('Réinitialiser') }}
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="card-body p-0">
        @if($activities->isEmpty())
            <div class="text-center py-40">
                <iconify-icon icon="solar:document-outline" class="text-6xl text-secondary-light mb-16"></iconify-icon>
                <p class="text-secondary-light">{{ __('Aucune activité') }}</p>
            </div>
        @else
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table sm-table mb-0">
                    <thead>
                        <tr>
                            <th style="width:50px">#</th>
                            <th>{{ __('Description') }}</th>
                            <th style="width:110px">{{ __('Événement') }}</th>
                            <th style="width:150px">{{ __('Utilisateur') }}</th>
                            <th style="width:160px">{{ __('Sujet') }}</th>
                            <th style="width:130px">{{ __('Journal') }}</th>
                            <th style="width:130px">{{ __('Date') }}</th>
                            <th style="width:60px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activities as $activity)
                        <tr>
                            <td class="text-sm text-secondary-light">{{ $activity->id }}</td>
                            <td class="text-sm">{{ $activity->description }}</td>
                            <td>
                                @if($activity->event)
                                    @php
                                        $badgeClass = match($activity->event) {
                                            'created' => 'bg-success-focus text-success-main',
                                            'updated' => 'bg-warning-focus text-warning-main',
                                            'deleted' => 'bg-danger-focus text-danger-main',
                                            default => 'bg-neutral-200 text-neutral-600',
                                        };
                                    @endphp
                                    <span class="border px-24 py-4 radius-4 fw-medium text-sm {{ $badgeClass }}">{{ ucfirst($activity->event) }}</span>
                                @else
                                    <span class="text-secondary-light">-</span>
                                @endif
                            </td>
                            <td class="text-sm">{{ $activity->causer?->name ?? __('Système') }}</td>
                            <td>
                                @if($activity->subject_type)
                                    <span class="bg-primary-50 text-primary-600 border border-primary-main px-24 py-4 radius-4 fw-medium text-sm">
                                        {{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}
                                    </span>
                                @else
                                    <span class="text-secondary-light">-</span>
                                @endif
                            </td>
                            <td>
                                @if($activity->log_name)
                                    <span class="bg-neutral-200 text-neutral-600 border border-neutral-400 px-24 py-4 radius-4 fw-medium text-sm">{{ $activity->log_name }}</span>
                                @else
                                    <span class="text-secondary-light">-</span>
                                @endif
                            </td>
                            <td class="text-sm text-secondary-light">{{ $activity->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <button wire:click="showDetail({{ $activity->id }})" class="btn btn-sm btn-outline-primary-600 radius-4" title="{{ __('Voir le détail') }}">
                                    <iconify-icon icon="solar:eye-outline"></iconify-icon>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span class="text-secondary-light text-sm">{{ $activities->total() }} {{ __('entrée(s)') }}</span>
                {{ $activities->links() }}
            </div>
        @endif
    </div>

    {{-- Modal détail --}}
    @if($detailActivity)
    <div class="modal fade show d-block" style="background:rgba(0,0,0,.5);z-index:1050" wire:click.self="closeDetail">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content radius-8">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:history-outline" class="text-primary-600"></iconify-icon>
                        {{ __('Détail activité') }} #{{ $detailActivity->id }}
                    </h5>
                    <button wire:click="closeDetail" class="btn-close" aria-label="{{ __('Fermer') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="row gy-3 mb-20">
                        <div class="col-md-6">
                            <span class="text-secondary-light text-sm d-block mb-4">{{ __('Description') }}</span>
                            <span class="fw-medium">{{ $detailActivity->description }}</span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-secondary-light text-sm d-block mb-4">{{ __('Utilisateur') }}</span>
                            <span class="fw-medium">{{ $detailActivity->causer?->name ?? __('Système') }}</span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-secondary-light text-sm d-block mb-4">{{ __('Sujet') }}</span>
                            <span class="fw-medium">
                                @if($detailActivity->subject_type)
                                    {{ class_basename($detailActivity->subject_type) }} #{{ $detailActivity->subject_id }}
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                        <div class="col-md-3">
                            <span class="text-secondary-light text-sm d-block mb-4">{{ __('Événement') }}</span>
                            @if($detailActivity->event)
                                @php
                                    $badgeClass = match($detailActivity->event) {
                                        'created' => 'bg-success-focus text-success-main',
                                        'updated' => 'bg-warning-focus text-warning-main',
                                        'deleted' => 'bg-danger-focus text-danger-main',
                                        default => 'bg-neutral-200 text-neutral-600',
                                    };
                                @endphp
                                <span class="border px-24 py-4 radius-4 fw-medium text-sm {{ $badgeClass }}">{{ ucfirst($detailActivity->event) }}</span>
                            @else
                                <span class="text-secondary-light">-</span>
                            @endif
                        </div>
                        <div class="col-md-3">
                            <span class="text-secondary-light text-sm d-block mb-4">{{ __('Date') }}</span>
                            <span class="fw-medium">{{ $detailActivity->created_at->format('d/m/Y H:i:s') }}</span>
                        </div>
                    </div>

                    @php $properties = $detailActivity->properties ?? collect(); @endphp

                    @if($properties->has('old') && $properties->has('attributes'))
                        <h6 class="fw-semibold mb-12">{{ __('Modifications') }}</h6>
                        <div class="table-responsive mb-20">
                            <table class="table bordered-table sm-table mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ __('Champ') }}</th>
                                        <th>{{ __('Avant') }}</th>
                                        <th>{{ __('Après') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($properties['attributes'] as $key => $value)
                                    <tr>
                                        <td class="fw-medium">{{ $key }}</td>
                                        <td class="text-danger-main">{{ $properties['old'][$key] ?? '-' }}</td>
                                        <td class="text-success-main">{{ $value }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <h6 class="fw-semibold mb-12">{{ __('Propriétés (JSON)') }}</h6>
                    <pre class="bg-neutral-50 p-16 radius-8 mb-0 text-sm" style="max-height:300px;overflow:auto">{{ json_encode($properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
                <div class="modal-footer">
                    <button wire:click="closeDetail" class="btn btn-neutral-600 radius-8">{{ __('Fermer') }}</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
