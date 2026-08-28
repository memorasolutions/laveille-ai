{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- Carte compacte pour la section Highlights --}}

@php
    $host = $tool->url ? parse_url($tool->url, PHP_URL_HOST) : '';
    $screenshotSrc = $tool->screenshot
        ? (str_starts_with($tool->screenshot, 'http') ? $tool->screenshot : asset($tool->screenshot).'?v='.($tool->updated_at?->timestamp ?? '0'))
        : '';
    $pricingLabels = \Modules\Directory\Support\PricingCategories::labels();
    // Couleurs assombries pour contraste AAA (7:1+) avec le texte blanc superposé (WCAG 1.4.6) : audit 2026-07-03
    $gradientColors = ['#0B7285','#1a365d','#8E44AD','#854914','#176638','#9F3429','#205D86','#794E09'];
    $gIdx = abs(crc32($tool->name)) % count($gradientColors);
    // 2026-08-28 - bascule sur le compteur "propre" (clicks_count_verified, filtré anti-robot
    // et dédupliqué via Modules\Core\Services\ViewCounterService) : clicks_count reste affiché
    // nulle part, il porte un historique pollué par les robots (voir migration
    // 2026_08_28_100000_add_clicks_count_verified_to_directory_tools.php). Seuil configurable
    // (réglage directory.*, même convention que directory.similar_tools_limit) : sous ce seuil,
    // rien ne s'affiche plutôt qu'un chiffre dérisoire ("2 vues") sur une fiche en réalité très
    // consultée mais dont le compteur propre est encore jeune - un badge absent n'induit personne
    // en erreur, un badge trompeur si.
    $viewsVerifiedMinDisplay = \Modules\Settings\Facades\Settings::get('directory.views_verified_min_display', 10);
@endphp

<a href="{{ $tool->getPublicUrl() }}" class="rt-hl-card" title="{{ $tool->name }}" style="position:relative;">
    {{-- 2026-05-05 #135 : badge YouTube rouge tutos sur card highlight --}}
    @if(($tool->tutorials_count ?? 0) > 0)
        <span style="position:absolute;top:6px;right:6px;z-index:3;display:inline-flex;align-items:center;gap:3px;background:#0B7285;color:#fff;font-size:10px;font-weight:700;padding:2px 6px;border-radius:3px;line-height:1.2;box-shadow:0 1px 3px rgba(0,0,0,.2);" title="{{ $tool->tutorials_count }} {{ $tool->tutorials_count > 1 ? __('tutoriels') : __('tutoriel') }}">
            <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            <span>{{ $tool->tutorials_count }}</span>
        </span>
    @endif
    <div class="rt-hl-img" style="{{ $screenshotSrc ? '' : 'background: linear-gradient(135deg, ' . $gradientColors[$gIdx] . ', ' . $gradientColors[($gIdx + 1) % count($gradientColors)] . ');' }}">
        @if($screenshotSrc)
            <img src="{{ $screenshotSrc }}" alt="{{ $tool->name }}" loading="lazy"
                 onerror="this.onerror=null; this.src='/images/directory-fallback.svg';">
        @else
            <span class="rt-hl-img-text">{{ Str::limit($tool->name, 15) }}</span>
        @endif
    </div>
    <div class="rt-hl-body">
        <div class="rt-hl-name">{{ $tool->name }}</div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:4px;">
            <span class="rt-badge badge-{{ $tool->pricing }}">{{ $pricingLabels[$tool->pricing] ?? ucfirst($tool->pricing) }}</span>
            @if(($tool->clicks_count_verified ?? 0) >= $viewsVerifiedMinDisplay)
                <span style="display:inline-flex;align-items:center;gap:3px;color:var(--c-text-muted, #52586a);font-size:11px;font-weight:600;" title="{{ number_format($tool->clicks_count_verified, 0, ',', ' ') }} {{ __('vues') }}">
                    👁 {{ number_format($tool->clicks_count_verified, 0, ',', ' ') }}
                </span>
            @endif
            <x-directory::tool-freshness-badge :tool="$tool" :compact="true" />
        </div>
    </div>
</a>
