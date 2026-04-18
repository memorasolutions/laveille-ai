@props(['tool', 'size' => 'sm'])

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

    $isSmall = $size === 'sm';
    $badgeStyle = implode(';', array_filter([
        'display:inline-flex',
        'align-items:center',
        'gap:5px',
        'font-weight:600',
        'color:#fff',
        'border-radius:999px',
        'white-space:nowrap',
        'line-height:1.3',
        'box-shadow:0 2px 6px rgba(0,0,0,.18)',
        'background-color:' . $color . 'F2',
        $isSmall ? 'padding:4px 10px;font-size:11px;position:absolute;top:12px;right:12px;z-index:3' : 'padding:6px 14px;font-size:13px',
    ]));
@endphp
<span
    {{ $attributes->merge(['style' => $badgeStyle]) }}
    aria-label="Statut : {{ $label }}"
    title="{{ $label }}"
>
    <i class="{{ $faClass }}" aria-hidden="true" style="font-size:inherit"></i>
    {{ $label }}
</span>
@endif
