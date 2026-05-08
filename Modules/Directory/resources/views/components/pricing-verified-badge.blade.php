{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{--
    Badge "Vérifié il y a X jours" — DRY réutilisable cards + show.blade.
    Usage : <x-directory::pricing-verified-badge :tool="$tool" />

    Couleurs adaptatives selon âge (Wirecutter pattern + WCAG AAA contraste) :
      - vert  fresh   < 30j  : 🟢 Vérifié il y a Xj
      - jaune aging   30-90j : 🟡 Vérifié il y a Xj
      - orange stale  > 90j  : 🟠 À revérifier
      - gris  never          : (rien affiché)
--}}
@props(['tool', 'compact' => false])

@php
    $latestAudit = \Modules\Directory\Models\ToolPricingAudit::query()
        ->where('directory_tool_id', $tool->id)
        ->where('review_status', '!=', 'rejected')
        ->orderByDesc('audited_at')
        ->first();

    $tier = $latestAudit?->freshnessTier() ?? 'never';
    $daysAgo = $latestAudit && $latestAudit->audited_at
        ? (int) $latestAudit->audited_at->diffInDays(now())
        : null;
@endphp

@if($latestAudit)
@once
<style>
    .lv-verified-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 50px;
        font-size: 10px;
        font-weight: 700;
        line-height: 1.4;
        white-space: nowrap;
        cursor: help;
    }
    .lv-verified-badge--fresh { background: #D1FAE5; color: #065F46; }
    .lv-verified-badge--aging { background: #FEF3C7; color: #92400E; }
    .lv-verified-badge--stale { background: #FED7AA; color: #9A3412; }
    .lv-verified-badge--icon { font-size: 11px; line-height: 1; }
</style>
@endonce
<span class="lv-verified-badge lv-verified-badge--{{ $tier }}"
      title="{{ __('Tarif vérifié le') }} {{ $latestAudit->audited_at->isoFormat('LL') }} · {{ __('Confidence') }} {{ $latestAudit->confidence }}/100"
      role="img"
      aria-label="{{ __('Tarif vérifié il y a') }} {{ $daysAgo }} {{ __('jours') }}, {{ __('confidence') }} {{ $latestAudit->confidence }}/100">
    <span class="lv-verified-badge--icon" aria-hidden="true">@switch($tier)@case('fresh')🟢@break @case('aging')🟡@break @case('stale')🟠@break @endswitch</span>
    @if($tier === 'stale')
        @if(!$compact){{ __('À revérifier') }}@endif
    @else
        @if($compact)
            {{ $daysAgo }}j
        @else
            {{ __('Vérifié il y a') }} {{ $daysAgo }}j
        @endif
    @endif
</span>
@endif
