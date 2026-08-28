@php
    $statusValue = $monitor->status->value;
    $statusLabel = match ($statusValue) {
        'up'      => __('statut::messages.operational'),
        'down'    => __('statut::messages.down'),
        'paused'  => __('statut::messages.paused'),
        default   => __('statut::messages.unknown'),
    };

    $lastCheckLabel = $monitor->lastCheckAt
        ? \Illuminate\Support\Carbon::createFromTimestamp($monitor->lastCheckAt)->diffForHumans()
        : __('statut::messages.never_checked');

    $responseTime = $monitor->responseTimeMs !== null
        ? $monitor->responseTimeMs . ' ms'
        : null;

    $uptime = $monitor->uptime ?? [];
    $periods = ['24h' => $uptime['24h'] ?? null, '7d' => $uptime['7d'] ?? null, '30d' => $uptime['30d'] ?? null, '90d' => $uptime['90d'] ?? null];
@endphp

<article class="statut-card statut-card--{{ $statusValue }}" aria-label="{{ $monitor->name }} – {{ $statusLabel }}">
    <div class="statut-card-header">
        <h3>{{ $monitor->name }}</h3>
        <span class="statut-pill statut-pill--{{ $statusValue }}">{{ $statusLabel }}</span>
    </div>

    <div class="statut-status-text">
        <span>{{ $monitor->type }}</span>
        <span aria-hidden="true">·</span>
        <span>{{ $lastCheckLabel }}</span>
        @if($responseTime)
            <span aria-hidden="true">·</span>
            <span>{{ $responseTime }}</span>
        @endif
    </div>

    <div class="statut-uptime-bar" role="img" aria-label="{{ __('statut::messages.uptime_periods') }}">
        @foreach($periods as $period => $percentage)
            @php
                if ($percentage === null) {
                    $color = 'var(--statut-unknown)';
                    $label = __('statut::messages.na');
                } else {
                    $pct = (float) $percentage;
                    $color = $pct >= 99 ? 'var(--statut-up)' : ($pct >= 95 ? 'var(--statut-paused)' : 'var(--statut-down)');
                    $label = number_format($pct, 2, ',', ' ') . ' %';
                }
            @endphp
            <div
                class="statut-uptime-segment"
                style="background-color: {{ $color }};"
                title="{{ $period }} : {{ $label }}"
            ></div>
        @endforeach
    </div>
</article>
