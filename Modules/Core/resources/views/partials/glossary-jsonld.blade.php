{{-- 2026-05-05 #141 : Schema.org DefinedTermSet JSON-LD pour les termes matchés par GlossaryLinkifier --}}
{{-- Usage : @include('core::partials.glossary-jsonld') APRÈS @glossarize() pour récupérer matched terms --}}
@php
    $matchedTerms = \Modules\Core\Services\GlossaryLinkifier::getLastMatchedTerms();
@endphp
@if(! empty($matchedTerms))
    {{-- CSS minimal .glossary-link (chargé une seule fois par layout) --}}
    @once
        <style>
            a.glossary-link {
                color: var(--c-primary, #0B7285);
                text-decoration: underline;
                text-decoration-style: dotted;
                text-decoration-thickness: 1px;
                text-underline-offset: 3px;
                cursor: help;
                font-weight: 500;
                transition: color 0.15s ease;
            }
            a.glossary-link:hover, a.glossary-link:focus {
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
