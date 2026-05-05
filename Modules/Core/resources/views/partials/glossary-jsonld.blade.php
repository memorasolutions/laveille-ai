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
                color: var(--c-primary, #0B7285);
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
                outline: 2px solid var(--c-primary, #0B7285);
                outline-offset: 2px;
                border-radius: 2px;
            }

            /* Tooltip Memora hybride glassmorphism (charte teal + trend 2026 NN/g) */
            /* Specs : min 240 / max 320 / padding 14x18 / font 0.875rem / delay 200ms / fade 200ms */
            a.glossary-link::after {
                content: attr(data-tooltip);
                position: absolute;
                bottom: calc(100% + 12px);
                left: 50%;
                transform: translateX(-50%) translateY(6px);
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
                max-width: 320px;
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
                position: absolute;
                bottom: calc(100% + 6px);
                left: 50%;
                transform: translateX(-50%) translateY(6px);
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
            a.glossary-link:focus-visible::after,
            a.glossary-link:hover::before,
            a.glossary-link:focus-visible::before {
                opacity: 1;
                visibility: visible;
                transform: translateX(-50%) translateY(0);
                transition-delay: 200ms;
            }
            /* Position fallback : si pas de place en haut, afficher en bas */
            a.glossary-link[data-tooltip-pos="bottom"]::after {
                bottom: auto;
                top: calc(100% + 10px);
            }
            a.glossary-link[data-tooltip-pos="bottom"]::before {
                bottom: auto;
                top: calc(100% + 4px);
                border-top-color: transparent;
                border-bottom-color: var(--c-dark, #053d4a);
            }
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
