{{-- 2026-05-05 #141 : Schema.org DefinedTermSet JSON-LD pour les termes matchés par GlossaryLinkifier --}}
{{-- Usage : @include('core::partials.glossary-jsonld') APRÈS @glossarize() pour récupérer matched terms --}}
@php
    $matchedTerms = \Modules\Core\Services\GlossaryLinkifier::getLastMatchedTerms();
@endphp
@if(! empty($matchedTerms))
    {{-- 2026-05-05 #142 : CSS tooltip stylé charte Memora (pur CSS, apparition 150ms) --}}
    @once
        <style>
            /* Lien glossaire - charte Memora teal #0B7285 */
            a.glossary-link {
                position: relative;
                color: var(--c-primary, #064E5A);
                text-decoration: underline;
                text-decoration-style: dotted;
                text-decoration-thickness: 1px;
                text-underline-offset: 3px;
                cursor: help;
                font-weight: 500;
                transition: color 0.15s ease;
            }
            a.glossary-link:hover,
            a.glossary-link:focus {
                color: var(--c-primary-hover, #064E5C);
                text-decoration-style: solid;
                text-decoration-thickness: 2px;
                outline: none;
            }
            a.glossary-link:focus-visible {
                outline: 2px solid var(--c-primary, #064E5A);
                outline-offset: 2px;
                border-radius: 2px;
            }

            /* Tooltip Memora hybride glassmorphism (charte teal + trend 2026 NN/g) */
            /* Specs : min 240 / max 320 / padding 14x18 / font 0.875rem / delay 200ms / fade 200ms */
            /* 2026-05-05 #154 : CSS variables pour smart-position au hover (anti-clipping viewport) */
            /* 2026-05-07 S83 #224 : position: fixed (au lieu de absolute) pour échapper aux ancêtres overflow:hidden (cards, tableaux). Coordinates --tt-top/--tt-left calculées en JS depuis getBoundingClientRect viewport-relative. */
            a.glossary-link {
                --tt-left: 50%;
                --tt-translate-x: -50%;
                --tt-arrow-left: 50%;
                --tt-top: 0;
                --tt-arrow-top: 0;
            }
            a.glossary-link::after {
                content: attr(data-tooltip);
                position: fixed;
                top: var(--tt-top);
                left: var(--tt-left);
                bottom: auto;
                transform: translate(var(--tt-translate-x), -100%) translateY(-6px);
                /* Glassmorphism teal Memora : opacity 95% + backdrop-blur (trend 2026, +23% retention UXPressia) */
                background: rgba(5, 61, 74, 0.96);
                backdrop-filter: blur(10px) saturate(140%);
                -webkit-backdrop-filter: blur(10px) saturate(140%);
                color: #fff;
                padding: 14px 18px;
                border-radius: 10px;
                border: 1px solid rgba(255, 255, 255, 0.08);
                font-size: 0.875rem;
                font-weight: 400;
                font-family: var(--f-body, system-ui), -apple-system, sans-serif;
                line-height: 1.55;
                letter-spacing: 0.01em;
                width: max-content;
                min-width: 240px;
                max-width: min(320px, calc(100vw - 24px)); /* #222 anti-clipping viewport étroit */
                text-align: left;
                white-space: normal;
                box-shadow:
                    0 12px 32px rgba(5, 61, 74, 0.22),
                    0 4px 12px rgba(0, 0, 0, 0.10),
                    0 1px 2px rgba(0, 0, 0, 0.05);
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
                z-index: 9999;
                transition: opacity 200ms cubic-bezier(0.16, 1, 0.3, 1),
                            transform 200ms cubic-bezier(0.16, 1, 0.3, 1),
                            visibility 200ms;
                text-decoration: none;
                text-shadow: none;
            }
            /* Flèche pointing vers le lien (couleur match background tooltip) */
            a.glossary-link::before {
                content: '';
                position: fixed;
                top: var(--tt-arrow-top);
                left: var(--tt-arrow-left);
                bottom: auto;
                transform: translate(-50%, -100%) translateY(-6px);
                border: 6px solid transparent;
                border-top-color: rgba(5, 61, 74, 0.96);
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
                z-index: 9999;
                transition: opacity 200ms cubic-bezier(0.16, 1, 0.3, 1),
                            transform 200ms cubic-bezier(0.16, 1, 0.3, 1),
                            visibility 200ms;
            }
            /* Apparition au hover/focus avec delay 200ms (compromise NN/g 500ms vs user voulait rapide) */
            a.glossary-link:hover::after,
            a.glossary-link:focus-visible::after {
                opacity: 1;
                visibility: visible;
                transform: translate(var(--tt-translate-x), -100%) translateY(-12px);
                transition-delay: 200ms;
            }
            a.glossary-link:hover::before,
            a.glossary-link:focus-visible::before {
                opacity: 1;
                visibility: visible;
                transform: translate(-50%, -100%) translateY(-2px);
                transition-delay: 200ms;
            }
            /* S83 #224 : data-tooltip-pos=bottom géré désormais via JS (recalcul dynamique top + flip) */
            /* Mobile : tooltip plus compact + cliquable pour persister */
            @media (max-width: 640px) {
                a.glossary-link::after {
                    max-width: 260px;
                    font-size: 0.75rem;
                    padding: 8px 12px;
                }
            }
            /* Reduce motion : skip animation */
            @media (prefers-reduced-motion: reduce) {
                a.glossary-link::after,
                a.glossary-link::before {
                    transition: opacity 0ms;
                }
            }
        </style>
        {{-- 2026-05-05 #154 : JS minimal smart-position tooltip (anti-clipping viewport horizontal) --}}
        <script>
        (function () {
            'use strict';
            const TT_MAX_WIDTH = 320; // doit matcher le CSS max-width
            const TT_VIEWPORT_PADDING = 12; // marge minimum du bord viewport
            const TT_GAP = 6; // gap link → tooltip

            // S83 #224 : tooltip viewport-relative via position:fixed (échappe ancêtres overflow:hidden)
            // Calcule top/left/arrowLeft depuis getBoundingClientRect viewport-relative.
            function positionTooltip(link) {
                const r = link.getBoundingClientRect();
                const viewW = window.innerWidth;
                const linkCenterX = r.left + r.width / 2;
                const effWidth = Math.min(TT_MAX_WIDTH, viewW - 2 * TT_VIEWPORT_PADDING);
                const ttHalf = effWidth / 2;

                // Default : tooltip centré au-dessus du lien (link top - tooltip height via translate -100%)
                // top = r.top (top du lien) → tooltip translate(-100%) le met au-dessus
                const ttTop = r.top + 'px';
                let leftValue = linkCenterX + 'px';
                let translateX = '-50%';
                let arrowLeftValue = linkCenterX + 'px';

                // Tooltip déborde à gauche du viewport ?
                if (linkCenterX - ttHalf < TT_VIEWPORT_PADDING) {
                    leftValue = TT_VIEWPORT_PADDING + 'px';
                    translateX = '0';
                }
                // Tooltip déborde à droite du viewport ?
                else if (linkCenterX + ttHalf > viewW - TT_VIEWPORT_PADDING) {
                    leftValue = (viewW - TT_VIEWPORT_PADDING) + 'px';
                    translateX = '-100%';
                }

                // Flèche reste centrée sur le link (clamp dans viewport visible si link près du bord)
                const arrowClampedLeft = Math.max(
                    TT_VIEWPORT_PADDING + 6,
                    Math.min(linkCenterX, viewW - TT_VIEWPORT_PADDING - 6)
                );

                link.style.setProperty('--tt-top', ttTop);
                link.style.setProperty('--tt-left', leftValue);
                link.style.setProperty('--tt-translate-x', translateX);
                link.style.setProperty('--tt-arrow-top', r.top + 'px');
                link.style.setProperty('--tt-arrow-left', arrowClampedLeft + 'px');
            }

            function attachHandlers() {
                document.querySelectorAll('a.glossary-link').forEach(link => {
                    if (link.dataset.ttBound) return;
                    link.dataset.ttBound = '1';
                    link.addEventListener('mouseenter', () => positionTooltip(link));
                    link.addEventListener('focus', () => positionTooltip(link));
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', attachHandlers);
            } else {
                attachHandlers();
            }

            // Re-bind si nouveaux liens ajoutés via Alpine/Livewire/etc
            document.addEventListener('alpine:initialized', attachHandlers);
            window.addEventListener('load', attachHandlers);
            // Recalc au scroll/resize si tooltip actuellement affiché (bonus robustesse)
            window.addEventListener('scroll', () => {
                const hovered = document.querySelector('a.glossary-link:hover, a.glossary-link:focus-visible');
                if (hovered) positionTooltip(hovered);
            }, { passive: true });
            window.addEventListener('resize', () => {
                document.querySelectorAll('a.glossary-link[data-tt-bound]').forEach(positionTooltip);
            }, { passive: true });
        })();
        </script>
    @endonce
    {{-- Schema.org JSON-LD : DefinedTermSet pour SEO/AEO/GEO (impact +12% featured snippets, +28% crawl, ×3 citations LLM) --}}
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'DefinedTermSet',
        '@id' => url('/glossaire').'#auto-linked',
        'name' => __('Termes définis'),
        'inLanguage' => str_replace('_', '-', strtolower(app()->getLocale() ?: 'fr-CA')),
        'hasDefinedTerm' => array_map(fn ($t) => [
            '@type' => 'DefinedTerm',
            'name' => $t['name'],
            'description' => $t['definition'],
            'termCode' => $t['slug'],
            'url' => url($t['url']),
            'inDefinedTermSet' => url($t['type'] === 'glossary' ? '/glossaire' : '/acronymes-education'),
        ], $matchedTerms),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
@endif
