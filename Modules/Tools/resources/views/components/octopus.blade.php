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

    $defaultLabels = [
        'intro'       => "Octopus curieux, mascotte des Sentiers de l'IA",
        'neutral'     => "Octopus, mascotte des Sentiers de l'IA",
        'celebrating' => "Octopus célèbre une réussite",
        'explorer'    => "Octopus explore avec des outils",
        'share'       => "Octopus partage une découverte",
        'confident'   => "Octopus, prêt pour le défi",
        'confused'    => "Octopus perplexe, cherche dans les courants",
        'thinking'    => "Octopus réfléchit",
        'sleeping'    => "Octopus se repose",
        'surprised'   => "Octopus surpris",
        'happy'       => "Octopus content",
        'loved'       => "Octopus avec des yeux en cœur",
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
