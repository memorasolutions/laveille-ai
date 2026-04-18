@props(['tool'])

@if(! $tool->is_lifecycle_active)
@php
    $iconMap = [
        'fa-circle-check'          => 'fa-check-circle',
        'fa-flask'                 => 'fa-flask',
        'fa-pause-circle'          => 'fa-pause-circle',
        'fa-tag'                   => 'fa-tag',
        'fa-shuffle'               => 'fa-random',
        'fa-handshake'             => 'fa-handshake-o',
        'fa-circle-xmark'          => 'fa-times-circle',
        'fa-triangle-exclamation'  => 'fa-exclamation-triangle',
    ];

    $rawIcon = $tool->lifecycle_icon;
    $faClass = 'fa ' . ($iconMap[$rawIcon] ?? $rawIcon);
    $label   = $tool->lifecycle_label;
    $color   = $tool->lifecycle_color;
    $status  = $tool->lifecycle_status;
    $date    = $tool->lifecycle_date;
    $notes   = $tool->lifecycle_notes;

    $messages = [
        'closed'   => 'Cet outil est fermé définitivement.',
        'acquired' => 'Cet outil a été acquis par une autre entreprise.',
        'renamed'  => 'Cet outil a été renommé.',
        'pivoted'  => 'Cet outil a pivoté vers un nouveau positionnement.',
        'paused'   => 'Cet outil est temporairement en pause.',
        'scam'     => '⚠️ Cet outil est signalé comme arnaque. Évitez-le.',
        'beta'     => 'Cet outil est en phase bêta — fonctionnalités en développement.',
    ];
    $message = $messages[$status] ?? ('Statut : ' . $label);

    $bgColor     = $color . '1A'; // ~10% opacité en hex
    $borderColor = $color;

    $hasReplacement = $tool->hasReplacement();
    $replacementUrl = null;
    if ($hasReplacement) {
        if ($tool->lifecycle_replacement_url) {
            $replacementUrl = $tool->lifecycle_replacement_url;
        } elseif ($tool->lifecycle_replacement_tool_id && method_exists($tool, 'lifecycleReplacement') && $tool->lifecycleReplacement) {
            $replacementUrl = route('directory.show', $tool->lifecycleReplacement);
        }
    }
@endphp
<div
    {{ $attributes }}
    role="alert"
    style="
        background-color: {{ $bgColor }};
        border-left: 4px solid {{ $borderColor }};
        padding: 16px 20px;
        margin-bottom: 24px;
        border-radius: 6px;
        display: flex;
        align-items: flex-start;
        gap: 16px;
        font-family: inherit;
        color: var(--c-text-muted, #555);
    "
>
    <div style="flex-shrink:0;display:flex;align-items:center;justify-content:center;width:40px;height:40px">
        <i class="{{ $faClass }}" aria-hidden="true" style="font-size:32px;color:{{ $color }};line-height:1"></i>
    </div>

    <div style="flex:1;min-width:0">
        <h3 style="margin:0 0 4px 0;font-size:16px;font-weight:700;color:#1a1a1a;line-height:1.4">
            {{ $message }}
            @if($date)
                <span style="font-weight:400;font-size:14px;opacity:.75">
                    — depuis {{ $date->translatedFormat('F Y') }}
                </span>
            @endif
        </h3>

        @if($notes)
            <p style="margin:6px 0 0 0;font-size:14px;line-height:1.55;color:var(--c-text-muted,#666)">
                {{ $notes }}
            </p>
        @endif

        @if($hasReplacement && $replacementUrl)
            <a
                href="{{ $replacementUrl }}"
                @if(str_starts_with($replacementUrl, 'http')) target="_blank" rel="noopener noreferrer" @endif
                style="
                    display:inline-flex;
                    align-items:center;
                    gap:6px;
                    margin-top:12px;
                    padding:8px 18px;
                    font-size:14px;
                    font-weight:600;
                    color:#fff;
                    background-color:{{ $color }};
                    border:none;
                    border-radius:6px;
                    text-decoration:none;
                    cursor:pointer;
                    transition:opacity .2s;
                "
                onmouseover="this.style.opacity='0.85'"
                onmouseout="this.style.opacity='1'"
            >
                Voir le remplaçant <span aria-hidden="true" style="font-size:15px">→</span>
            </a>
        @endif
    </div>
</div>
@endif
