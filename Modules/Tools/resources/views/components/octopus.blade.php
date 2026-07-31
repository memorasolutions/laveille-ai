@props([
    'variant' => 'intro',
    'size' => 84,
    'animate' => true,
    'label' => null,
])
@php
    // #188 + #202 : composant DRY pour la mascotte Octopus.
    // 12 variantes : compositions (intro/neutral/celebrating/explorer/share/confident)
    // + expressions émotionnelles (confused/thinking/sleeping/surprised/happy/loved).
    // Source SVG : Modules/Tools/resources/assets/octopus/{variant}.svg
    // Tokens charte officielle : corps #0B7285 · tentacules #52B8C7 · pupilles #1a1d23.
    $allowedVariants = [
        'intro', 'neutral', 'celebrating', 'explorer', 'share', 'confident',
        'confused', 'thinking', 'sleeping', 'surprised', 'happy', 'loved',
    ];
    $variant = in_array($variant, $allowedVariants, true) ? $variant : 'intro';

    // Round 75 (2026-07-27, passe adversariale) : aria-label passés par __() - jamais traduits
    // auparavant, composant DRY réutilisé site-wide (pages d'erreur, under-construction, etc.).
    $defaultLabels = [
        'intro'       => __("Octopus curieux, mascotte des Sentiers de l'IA"),
        'neutral'     => __("Octopus, mascotte des Sentiers de l'IA"),
        'celebrating' => __("Octopus célèbre une réussite"),
        'explorer'    => __("Octopus explore avec des outils"),
        'share'       => __("Octopus partage une découverte"),
        'confident'   => __("Octopus, prêt pour le défi"),
        'confused'    => __("Octopus perplexe, cherche dans les courants"),
        'thinking'    => __("Octopus réfléchit"),
        'sleeping'    => __("Octopus se repose"),
        'surprised'   => __("Octopus surpris"),
        'happy'       => __("Octopus content"),
        'loved'       => __("Octopus avec des yeux en cœur"),
    ];
    $ariaLabel = $label ?? $defaultLabels[$variant];

    $svgPath = base_path('Modules/Tools/resources/assets/octopus/' . $variant . '.svg');
    $svgRaw = is_file($svgPath) ? file_get_contents($svgPath) : '';
    // Strip XML decl if present — sinon <? interprété comme tag PHP ouvrant dans Blade compile
    $svgRaw = preg_replace('/<\?xml[^?]*\?>\s*/', '', (string) $svgRaw);

    $injectedAttrs = ' role="img" aria-label="' . e($ariaLabel) . '" class="octopus-mascot octopus-mascot--' . $variant . ($animate ? ' octopus-mascot--animated' : '') . '" width="' . (int) $size . '" height="' . (int) $size . '"';
    $svgRaw = preg_replace('/(<svg\b)([^>]*)>/i', '$1$2' . $injectedAttrs . '>', $svgRaw, 1);
@endphp

@once
<style>
.octopus-mascot { flex-shrink: 0; display: inline-block; }
.octopus-mascot--animated { animation: octopusBob 3.2s ease-in-out infinite; transform-origin: center bottom; will-change: transform; }
@keyframes octopusBob { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
@media (prefers-reduced-motion: reduce) {
    .octopus-mascot--animated { animation: none; }
}
</style>
@endonce

{!! $svgRaw !!}
