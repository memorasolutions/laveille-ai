@props(['tool', 'compact' => false])

@php
    /**
     * S90 #43 — Badge fraîcheur outil (best practice EEAT/AI Overviews 2026).
     *
     * Affiche soit un badge de changement détecté (last_change_type), soit
     * un fallback "Vérifié il y a X jours" basé sur education_last_checked_at,
     * last_enriched_at ou updated_at. Aucun rendu si toutes les dates sont nulles.
     */

    $changeMap = [
        'price_changed'        => ['icon' => '💰', 'label' => __('Prix modifié'),         'class' => 'lv-badge-fresh-info'],
        'pricing_increased'    => ['icon' => '📈', 'label' => __('Prix augmenté'),        'class' => 'lv-badge-fresh-warn'],
        'pricing_decreased'    => ['icon' => '📉', 'label' => __('Prix réduit'),          'class' => 'lv-badge-fresh-good'],
        'new_api'              => ['icon' => '⚡', 'label' => __('Nouvelle API'),          'class' => 'lv-badge-fresh-info'],
        'new_feature'          => ['icon' => '✨', 'label' => __('Nouveauté'),             'class' => 'lv-badge-fresh-good'],
        'closed'               => ['icon' => '❌', 'label' => __('Plateforme fermée'),    'class' => 'lv-badge-fresh-danger'],
        'acquired'             => ['icon' => '🤝', 'label' => __('Racheté'),               'class' => 'lv-badge-fresh-purple'],
        'mobile_added'         => ['icon' => '📱', 'label' => __('Mobile ajouté'),         'class' => 'lv-badge-fresh-info'],
        'language_added'       => ['icon' => '🌍', 'label' => __('Langue ajoutée'),        'class' => 'lv-badge-fresh-good'],
        'education_added'      => ['icon' => '🎓', 'label' => __('Programme éducation'),   'class' => 'lv-badge-fresh-good'],
        'free_tier_added'      => ['icon' => '🎁', 'label' => __('Plan gratuit'),          'class' => 'lv-badge-fresh-good'],
        'beta_left'            => ['icon' => '🚀', 'label' => __('Sortie de bêta'),        'class' => 'lv-badge-fresh-good'],
        'deprecated'           => ['icon' => '⚠️',  'label' => __('Déprécié'),             'class' => 'lv-badge-fresh-warn'],
    ];

    $changeType = $tool->last_change_type ?? null;
    $changeAt   = $tool->last_change_detected_at ?? null;
    $changeNote = $tool->last_change_note ?? null;

    $verifiedAt = $tool->education_last_checked_at ?? $tool->last_enriched_at ?? $tool->updated_at ?? null;

    $variant = null;
    $diffStr = null;
    if ($changeType && isset($changeMap[$changeType]) && $changeAt) {
        $variant = $changeMap[$changeType];
        $diffStr = $changeAt->diffForHumans(['parts' => 1, 'short' => true]);
    } elseif ($verifiedAt && $verifiedAt->diffInDays(now()) <= 365) {
        $days = (int) $verifiedAt->diffInDays(now());
        if ($days <= 7) {
            $variant = ['icon' => '✓', 'label' => __('Vérifié récemment'), 'class' => 'lv-badge-fresh-good'];
        } elseif ($days <= 90) {
            $variant = ['icon' => '✓', 'label' => __('Vérifié'), 'class' => 'lv-badge-fresh-neutral'];
        } else {
            $variant = ['icon' => '🕒', 'label' => __('À revérifier'), 'class' => 'lv-badge-fresh-stale'];
        }
        $diffStr = $verifiedAt->diffForHumans(['parts' => 1, 'short' => true]);
    }
@endphp

@if($variant)
    @once
        @push('styles')
        <style>
            .lv-badge-fresh {
                display: inline-flex; align-items: center; gap: 5px;
                font-size: 11px; font-weight: 600; line-height: 1;
                padding: 4px 9px; border-radius: 999px;
                white-space: nowrap; letter-spacing: 0.2px;
                border: 1px solid transparent;
            }
            .lv-badge-fresh.lv-badge-fresh-good { background: #dcfce7; color: #14532d; border-color: #86efac; }
            .lv-badge-fresh.lv-badge-fresh-info { background: #dbeafe; color: #1e3a8a; border-color: #93c5fd; }
            .lv-badge-fresh.lv-badge-fresh-warn { background: #fef3c7; color: #78350f; border-color: #fcd34d; }
            .lv-badge-fresh.lv-badge-fresh-danger { background: #fee2e2; color: #7f1d1d; border-color: #fca5a5; }
            .lv-badge-fresh.lv-badge-fresh-purple { background: #ede9fe; color: #4c1d95; border-color: #c4b5fd; }
            .lv-badge-fresh.lv-badge-fresh-neutral { background: #f3f4f6; color: #374151; border-color: #d1d5db; }
            .lv-badge-fresh.lv-badge-fresh-stale { background: #fff7ed; color: #7c2d12; border-color: #fed7aa; }
            .lv-badge-fresh-time { opacity: 0.7; font-weight: 500; }
        </style>
        @endpush
    @endonce
    <span class="lv-badge-fresh {{ $variant['class'] }}"
          @if($changeNote) title="{{ $changeNote }}" @endif
          aria-label="{{ $variant['label'] }} — {{ $diffStr }}">
        <span aria-hidden="true">{{ $variant['icon'] }}</span>
        <span>{{ $variant['label'] }}</span>
        @unless($compact)
            <span class="lv-badge-fresh-time">· {{ $diffStr }}</span>
        @endunless
    </span>
@endif
