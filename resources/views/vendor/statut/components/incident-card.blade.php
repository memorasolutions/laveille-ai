@php
    $startedRaw = $incident->startedAt ?? '';
    $started = is_string($startedRaw) && trim($startedRaw) !== ''
        ? \Illuminate\Support\Carbon::parse($startedRaw)->diffForHumans()
        : __('statut::messages.unknown');
@endphp

<article class="statut-card statut-card--down" role="article">
    <h3>{{ $incident->monitorName }}</h3>
    <p>{{ __('statut::messages.started_at_label') }} {{ $started }}</p>
    @if(!empty($incident->cause))
        <p><strong>{{ __('statut::messages.cause_label') }}</strong> {{ $incident->cause }}</p>
    @endif
</article>
