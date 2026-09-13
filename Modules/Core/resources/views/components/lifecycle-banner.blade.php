@props(['tool'])

@if(! $tool->is_lifecycle_active)
@php
    // Message et icône lus depuis Modules/Core/app/Traits/HasLifecycleStatus.php (source unique -
    // ce composant portait auparavant ses propres tables $messages/$iconMap, recopiées à
    // l'identique dans index.blade.php, et les deux copies avaient déjà divergé).
    $rawIcon = $tool->lifecycle_icon;
    $faClass = 'fa ' . ($tool::lifecycleIconMap()[$rawIcon] ?? $rawIcon);
    $label   = $tool->lifecycle_label;
    $color   = $tool->lifecycle_color;
    $date    = $tool->lifecycle_date;
    $notes   = $tool->lifecycle_notes;
    $message = $tool->lifecycle_banner_message;

    $bgColor     = $color . '1A'; // ~10% opacité en hex
    $borderColor = $color;

    $hasReplacement = $tool->hasReplacement();
    $replacementUrl = null;
    if ($hasReplacement) {
        if ($tool->lifecycle_replacement_url) {
            $replacementUrl = $tool->lifecycle_replacement_url;
        } elseif ($tool->lifecycle_replacement_tool_id && method_exists($tool, 'lifecycleReplacement') && $tool->lifecycleReplacement) {
            $replacementUrl = $tool->lifecycleReplacement->getPublicUrl();
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
        color: var(--c-lifecycle-note, #3F4557);
    "
>
    <div style="flex-shrink:0;display:flex;align-items:center;justify-content:center;width:40px;height:40px">
        <i class="{{ $faClass }}" aria-hidden="true" style="font-size:32px;color:{{ $color }};line-height:1"></i>
    </div>

    <div style="flex:1;min-width:0">
        <h3 style="margin:0 0 4px 0;font-size:16px;font-weight:700;color:var(--c-lifecycle-title,#1a1a1a);line-height:1.4">
            {{ $message }}
            @if($date)
                <span style="font-weight:400;font-size:14px;opacity:.75">
                    – depuis {{ $date->translatedFormat('F Y') }}
                </span>
            @endif
        </h3>

        @if($notes)
            <p style="margin:6px 0 0 0;font-size:14px;line-height:1.55;color:var(--c-lifecycle-note,#3F4557)">
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
