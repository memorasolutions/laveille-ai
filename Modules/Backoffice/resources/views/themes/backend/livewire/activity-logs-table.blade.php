<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    {{-- Filtres --}}
    <div class="border-bottom pb-3 mb-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="position-relative">
                <input type="text" wire:model.live.debounce.300ms="search"
                       class="form-control form-control-sm ps-4"
                       placeholder="{{ __('Rechercher...') }}"
                       aria-label="Rechercher">
                <i data-lucide="search" class="icon-sm position-absolute" style="left:10px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:#6b7280;"></i>
            </div>
            <select wire:model.live="filterCauser" class="form-select form-select-sm w-auto" aria-label="Filtrer par utilisateur">
                <option value="">{{ __('Tous les utilisateurs') }}</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="filterLogName" class="form-select form-select-sm w-auto" aria-label="Filtrer par journal">
                <option value="">{{ __('Tous les journaux') }}</option>
                @foreach($logNames as $logName)
                    <option value="{{ $logName }}">{{ $logName }}</option>
                @endforeach
            </select>
            <select wire:model.live="filterEvent" class="form-select form-select-sm w-auto" aria-label="Filtrer par événement">
                <option value="">{{ __('Tous les événements') }}</option>
                @foreach($events as $event)
                    <option value="{{ $event }}">{{ ucfirst($event) }}</option>
                @endforeach
            </select>
            <input type="date" wire:model.live="dateFrom"
                   class="form-control form-control-sm w-auto"
                   title="{{ __('Date début') }}"
                   aria-label="{{ __('Date début') }}">
            <input type="date" wire:model.live="dateTo"
                   class="form-control form-control-sm w-auto"
                   title="{{ __('Date fin') }}"
                   aria-label="{{ __('Date fin') }}">
            <button wire:click="resetFilters"
                    class="btn btn-sm btn-light d-inline-flex align-items-center gap-1">
                <i data-lucide="x-circle" style="width:14px;height:14px;"></i> {{ __('Réinitialiser') }}
            </button>
        </div>
    </div>

    {{-- Table --}}
    @if($activities->isEmpty())
        <div class="text-center py-5">
            <i data-lucide="file-text" class="d-block mx-auto mb-2 text-muted" style="width:48px;height:48px;"></i>
            <p class="text-muted small">{{ __('Aucune activité') }}</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr class="border-bottom">
                        <th class="fw-medium" style="width:48px;">#</th>
                        <th class="fw-medium">{{ __('Description') }}</th>
                        <th class="fw-medium" style="width:112px;">{{ __('Événement') }}</th>
                        <th class="fw-medium" style="width:144px;">{{ __('Utilisateur') }}</th>
                        <th class="fw-medium" style="width:160px;">{{ __('Sujet') }}</th>
                        <th class="fw-medium" style="width:128px;">{{ __('Journal') }}</th>
                        <th class="fw-medium" style="width:128px;">{{ __('Date') }}</th>
                        <th class="fw-medium text-center" style="width:56px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activities as $activity)
                    <tr>
                        <td class="text-muted small">{{ $activity->id }}</td>
                        <td class="small text-body">{{ $activity->description }}</td>
                        <td>
                            @if($activity->event)
                                @php
                                    $badgeClass = match($activity->event) {
                                        'created' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                                        'updated' => 'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25',
                                        'deleted' => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                                        default   => 'bg-light text-muted border',
                                    };
                                @endphp
                                <span class="badge rounded py-1 px-2 fw-medium small {{ $badgeClass }}">
                                    {{ ucfirst($activity->event) }}
                                </span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $activity->causer?->name ?? __('Système') }}</td>
                        <td>
                            @if($activity->subject_type)
                                <span class="badge rounded py-1 px-2 fw-medium small bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                    {{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}
                                </span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td>
                            @if($activity->log_name)
                                <span class="badge rounded py-1 px-2 fw-medium small bg-light text-muted border">
                                    {{ $activity->log_name }}
                                </span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $activity->created_at->format('d/m/Y H:i') }}</td>
                        <td class="text-center">
                            <button wire:click="showDetail({{ $activity->id }})"
                                    class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center"
                                    style="width:28px;height:28px;padding:0;"
                                    title="{{ __('Voir le détail') }}">
                                <i data-lucide="eye" style="width:14px;height:14px;"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 pt-3 border-top">
            <span class="text-muted small">{{ $activities->total() }} {{ __('entrée(s)') }}</span>
            {{ $activities->links() }}
        </div>
    @endif

    {{-- Modal détail --}}
    @if($detailActivity)
    <div class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
         style="background:rgba(0,0,0,.4);z-index:1050;"
         wire:click.self="closeDetail">
        <div class="bg-white rounded-3 shadow w-100 overflow-auto" style="max-width:672px;max-height:90vh;">
            <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom">
                <h5 class="fw-semibold text-body mb-0 d-flex align-items-center gap-2">
                    <i data-lucide="history" class="text-primary" style="width:18px;height:18px;"></i>
                    {{ __('Détail activité') }} #{{ $detailActivity->id }}
                </h5>
                <button wire:click="closeDetail"
                        class="btn btn-sm btn-light d-inline-flex align-items-center justify-content-center"
                        style="width:32px;height:32px;padding:0;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>
            <div class="px-4 py-4">
                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <span class="text-muted small d-block mb-1">{{ __('Description') }}</span>
                        <span class="fw-medium small text-body">{{ $detailActivity->description }}</span>
                    </div>
                    <div class="col-6">
                        <span class="text-muted small d-block mb-1">{{ __('Utilisateur') }}</span>
                        <span class="fw-medium small text-body">{{ $detailActivity->causer?->name ?? __('Système') }}</span>
                    </div>
                    <div class="col-6">
                        <span class="text-muted small d-block mb-1">{{ __('Sujet') }}</span>
                        <span class="fw-medium small text-body">
                            @if($detailActivity->subject_type)
                                {{ class_basename($detailActivity->subject_type) }} #{{ $detailActivity->subject_id }}
                            @else
                                -
                            @endif
                        </span>
                    </div>
                    <div class="col-6">
                        <span class="text-muted small d-block mb-1">{{ __('Événement') }}</span>
                        @if($detailActivity->event)
                            @php
                                $badgeClass = match($detailActivity->event) {
                                    'created' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                                    'updated' => 'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25',
                                    'deleted' => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                                    default   => 'bg-light text-muted border',
                                };
                            @endphp
                            <span class="badge rounded py-1 px-2 fw-medium small {{ $badgeClass }}">
                                {{ ucfirst($detailActivity->event) }}
                            </span>
                        @else
                            <span class="text-muted small">-</span>
                        @endif
                    </div>
                    <div class="col-6">
                        <span class="text-muted small d-block mb-1">{{ __('Date') }}</span>
                        <span class="fw-medium small text-body">{{ $detailActivity->created_at->format('d/m/Y H:i:s') }}</span>
                    </div>
                </div>

                @php $properties = $detailActivity->properties ?? collect(); @endphp

                @if($properties->has('old') && $properties->has('attributes'))
                    <h6 class="fw-semibold small text-body mb-3">{{ __('Modifications') }}</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr class="border-bottom">
                                    <th class="fw-medium text-muted">{{ __('Champ') }}</th>
                                    <th class="fw-medium text-muted">{{ __('Avant') }}</th>
                                    <th class="fw-medium text-muted">{{ __('Après') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($properties['attributes'] as $key => $value)
                                <tr class="border-top">
                                    <td class="fw-medium text-body">{{ $key }}</td>
                                    <td class="text-danger">{{ $properties['old'][$key] ?? '-' }}</td>
                                    <td class="text-success">{{ $value }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <h6 class="fw-semibold small text-body mb-3">{{ __('Propriétés (JSON)') }}</h6>
                <pre class="bg-light border rounded p-3 small overflow-auto" style="max-height:192px;">{{ json_encode($properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
            <div class="d-flex justify-content-end px-4 py-3 border-top">
                <button wire:click="closeDetail"
                        class="btn btn-sm btn-light text-muted">
                    {{ __('Fermer') }}
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
