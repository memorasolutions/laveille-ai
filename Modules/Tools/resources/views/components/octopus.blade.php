@props([
    'variant' => 'intro',
    'size' => 84,
    'animate' => true,
    'label' => null,
])
@php
    // #188 : composant DRY pour la mascotte Octopus de "Les Sentiers de l'IA".
    // Variantes : intro · neutral · celebrating · explorer · share · confident.
    // Source SVG : Modules/Tools/resources/assets/octopus/{variant}.svg (Option C teal recolor).
    $allowedVariants = ['intro', 'neutral', 'celebrating', 'explorer', 'share', 'confident'];
    $variant = in_array($variant, $allowedVariants, true) ? $variant : 'intro';

    $defaultLabels = [
        'intro' => "Octopus curieux, mascotte des Sentiers de l'IA",
        'neutral' => "Octopus, mascotte des Sentiers de l'IA",
        'celebrating' => "Octopus célèbre une réussite",
        'explorer' => "Octopus explore avec des outils",
        'share' => "Octopus partage une découverte",
        'confident' => "Octopus, prêt pour le défi",
    ];
    $ariaLabel = $label ?? $defaultLabels[$variant];

    $svgPath = base_path('Modules/Tools/resources/assets/octopus/' . $variant . '.svg');
    $svgRaw = is_file($svgPath) ? file_get_contents($svgPath) : '';

    // Injecter accessibilité + classes + taille dans le <svg> racine sans regex fragile :
    // remplace l'attribut xmlns="..." par xmlns="..." role="img" aria-label="..." class="..."
    $injectedAttrs = ' role="img" aria-label="' . e($ariaLabel) . '" class="octopus-mascot octopus-mascot--' . $variant . ($animate ? ' octopus-mascot--animated' : '') . '" width="' . (int) $size . '" height="' . (int) $size . '"';
    $svgRaw = preg_replace('/(<svg\b)([^>]*)>/i', '$1$2' . $injectedAttrs . '>', $svgRaw, 1);
@endphp

@once
<style>
.octopus-mascot { flex-shrink: 0; }
.octopus-mascot--animated { animation: octopusBob 3.2s ease-in-out infinite; transform-origin: center bottom; will-change: transform; }
@keyframes octopusBob { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
@media (prefers-reduced-motion: reduce) {
    .octopus-mascot--animated { animation: none; }
}
</style>
@endonce

{!! $svgRaw !!}
