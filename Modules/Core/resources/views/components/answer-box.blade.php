@props([
    'title' => 'En bref',
    'summary' => null,
    'points' => [],
    'sources' => [],
    'updated' => null,
    'author' => null,
    'heading' => 'h2',
    'collapsible' => false,
])

@php
    $points = is_array($points) ? $points : (filled($points) ? [$points] : []);
    $sources = is_array($sources) ? $sources : [];
@endphp

@if(filled($summary) || ! empty($points))
    @if($collapsible)
        <section class="lv-answer-box" aria-label="Réponse rapide" itemprop="abstract">
            <details>
                <summary class="lv-answer-box__title">
                    <span><span aria-hidden="true">✦</span> {{ $title }}</span>
                    <span class="lv-answer-box__chevron" aria-hidden="true">▼</span>
                </summary>
                <div class="lv-answer-box__body">
                    @if(filled($summary))
                        <p class="lv-answer-box__summary">{{ $summary }}</p>
                    @endif

                    @if(! empty($points))
                        <ul class="lv-answer-box__points">
                            @foreach($points as $point)
                                @if(filled($point))
                                    <li>{{ $point }}</li>
                                @endif
                            @endforeach
                        </ul>
                    @endif

                    @if(! empty($sources))
                        <p class="lv-answer-box__sources">
                            <strong>Sources :</strong>
                            @foreach($sources as $index => $source)
                                @php
                                    $sLabel = is_array($source) ? ($source['label'] ?? '') : (string) $source;
                                    $sUrl = is_array($source) ? ($source['url'] ?? '') : '';
                                @endphp
                                @if(filled($sUrl) && filled($sLabel))
                                    @if($index > 0) · @endif
                                    <a href="{{ $sUrl }}" rel="noopener" target="_blank">{{ $sLabel }}</a>
                                @endif
                            @endforeach
                        </p>
                    @endif

                    @if(filled($updated))
                        <p class="lv-answer-box__meta">
                            Mis à jour le {{ $updated }}@if(filled($author)) · Par {{ $author }}@endif
                        </p>
                    @endif
                </div>
            </details>
        </section>
    @else
        <section class="lv-answer-box" aria-label="Réponse rapide" itemprop="abstract">
            <{{ $heading }} class="lv-answer-box__title">
                <span aria-hidden="true">✦</span> {{ $title }}
            </{{ $heading }}>

            @if(filled($summary))
                <p class="lv-answer-box__summary">{{ $summary }}</p>
            @endif

            @if(! empty($points))
                <ul class="lv-answer-box__points">
                    @foreach($points as $point)
                        @if(filled($point))
                            <li>{{ $point }}</li>
                        @endif
                    @endforeach
                </ul>
            @endif

            @if(! empty($sources))
                <p class="lv-answer-box__sources">
                    <strong>Sources :</strong>
                    @foreach($sources as $index => $source)
                        @php
                            $sLabel = is_array($source) ? ($source['label'] ?? '') : (string) $source;
                            $sUrl = is_array($source) ? ($source['url'] ?? '') : '';
                        @endphp
                        @if(filled($sUrl) && filled($sLabel))
                            @if($index > 0) · @endif
                            <a href="{{ $sUrl }}" rel="noopener" target="_blank">{{ $sLabel }}</a>
                        @endif
                    @endforeach
                </p>
            @endif

            @if(filled($updated))
                <p class="lv-answer-box__meta">
                    Mis à jour le {{ $updated }}@if(filled($author)) · Par {{ $author }}@endif
                </p>
            @endif
        </section>
    @endif

    @once
        <style>
            .lv-answer-box { background-color: #F3F8F9; border-left: 4px solid var(--c-primary, #064E5A); border-radius: 10px; padding: 18px 22px; margin: 0 0 24px; color: #111827; }
            .lv-answer-box__title { color: var(--c-primary, #064E5A); font-weight: 700; margin-bottom: 8px; font-size: 1.25rem; line-height: 1.5; }
            /* WCAG 2.2 AAA (1.4.6) : la règle globale ".wpo-blog-single-section p" (charte.css,
               calibrée 7:1 sur fond blanc) a une spécificité plus élevée qu'un simple ".lv-answer-box__summary"
               et écrase l'héritage de la couleur #111827 ci-dessus dès que ce composant est utilisé dans une
               page enveloppée par .wpo-blog-single-section (toutes les pages outils/blog) — sur le fond
               #F3F8F9 de la boîte, ça retombe à 6.54:1 (< 7:1). Override scopé, spécificité supérieure. */
            .lv-answer-box .lv-answer-box__summary { color: #111827; margin: 0 0 16px; line-height: 1.6; }
            .lv-answer-box__points { padding-left: 20px; margin: 0 0 16px; list-style-type: disc; line-height: 1.6; }
            .lv-answer-box__points li { margin-bottom: 6px; }
            .lv-answer-box__points li:last-child { margin-bottom: 0; }
            .lv-answer-box__sources { margin: 0 0 12px; font-size: 0.9375rem; line-height: 1.6; }
            .lv-answer-box__sources a { color: var(--c-primary, #064E5A); text-decoration: underline; text-decoration-thickness: 1px; text-underline-offset: 2px; }
            .lv-answer-box__meta { font-size: 0.875rem; color: #6B7280; margin: 0; line-height: 1.5; }

            /* Mode repliable (accordéon natif, sans JS) */
            .lv-answer-box details { display: block; }
            .lv-answer-box summary { display: flex; justify-content: space-between; align-items: center; cursor: pointer; list-style: none; outline-offset: 2px; gap: 12px; }
            .lv-answer-box summary::-webkit-details-marker { display: none; }
            .lv-answer-box summary .lv-answer-box__chevron { transition: transform 0.2s ease; color: var(--c-primary, #064E5A); font-size: 0.8rem; flex-shrink: 0; }
            .lv-answer-box details[open] summary .lv-answer-box__chevron { transform: rotate(180deg); }
            .lv-answer-box details > summary.lv-answer-box__title { margin-bottom: 0; }
            .lv-answer-box__body { margin-top: 12px; }

            @media (max-width: 639px) { .lv-answer-box { padding: 14px 16px; } }
        </style>
        {{-- Persistance de l'état ouvert/fermé de l'accordéon (mode collapsible) : survit au rafraîchissement. No-op sur les answer-box non repliables (articles). --}}
        <script>
        (function () {
            function lvAboxPersist() {
                document.querySelectorAll('.lv-answer-box details').forEach(function (d) {
                    if (d.__lvAbox) { return; }
                    d.__lvAbox = true;
                    var key = 'lv-abox:' + location.pathname;
                    try {
                        var s = localStorage.getItem(key);
                        if (s === 'open') { d.open = true; } else if (s === 'closed') { d.open = false; }
                    } catch (e) {}
                    d.addEventListener('toggle', function () {
                        try { localStorage.setItem(key, d.open ? 'open' : 'closed'); } catch (e) {}
                    });
                });
            }
            if (document.readyState !== 'loading') { lvAboxPersist(); } else { document.addEventListener('DOMContentLoaded', lvAboxPersist); }
        })();
        </script>
    @endonce
@endif
