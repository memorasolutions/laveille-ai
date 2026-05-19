@php
    if ($overview->allOperational()) {
        $statusClass = 'up';
        $message = __('statut::messages.all_operational');
    } elseif ($overview->down > 0 || $overview->activeIncidents > 0) {
        $statusClass = 'down';
        $message = __('statut::messages.incident_in_progress');
    } else {
        $statusClass = 'paused';
        $message = __('statut::messages.maintenance');
    }
@endphp

<div class="statut-badge-global statut-badge-global--{{ $statusClass }}" role="status" aria-live="polite">
    <p class="statut-badge-global__title">{{ $message }}</p>
    <p class="statut-badge-global__counts">
        {{ trans_choice('statut::messages.counts.total', $overview->total, ['count' => $overview->total]) }}
        @if($overview->down > 0)
            · {{ trans_choice('statut::messages.counts.down', $overview->down, ['count' => $overview->down]) }}
        @endif
        @if($overview->paused > 0)
            · {{ trans_choice('statut::messages.counts.paused', $overview->paused, ['count' => $overview->paused]) }}
        @endif
        @if($overview->activeIncidents > 0)
            · {{ trans_choice('statut::messages.counts.incidents', $overview->activeIncidents, ['count' => $overview->activeIncidents]) }}
        @endif
    </p>
</div>
