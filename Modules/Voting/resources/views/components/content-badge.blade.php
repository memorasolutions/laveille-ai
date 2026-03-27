{{-- Content badge — usage: @include('voting::components.content-badge', ['tier' => 'approved', 'isAdmin' => false]) --}}
@php
    $badges = config('voting.badge_styles', []);
    $badge = null;

    // Admin badge prend priorité visuelle
    if (!empty($isAdmin)) {
        $badge = $badges['admin'] ?? null;
    } elseif (isset($badges[$tier]) && $tier !== 'none') {
        $badge = $badges[$tier];
    }
@endphp

@if($badge)
<span x-show="tier !== 'none' || {{ !empty($isAdmin) ? 'true' : 'false' }}"
      style="display:inline-flex;align-items:center;gap:4px;padding:2px 10px;border-radius:12px;font-size:11px;font-weight:600;background:{{ $badge['bg'] }};color:{{ $badge['color'] }};white-space:nowrap;">
    {{ $badge['icon'] }} {{ __($badge['label']) }}
</span>
@endif
