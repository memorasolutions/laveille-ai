@props([
    'title' => 'En bref',
    'summary' => null,
    'points' => [],
    'sources' => [],
    'updated' => null,
    'author' => null,
    'heading' => 'h2',
])

@php
    $points = is_array($points) ? $points : (filled($points) ? [$points] : []);
    $sources = is_array($sources) ? $sources : [];
@endphp

@if(filled($summary) || ! empty($points))
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

    @once
        <style>
            .lv-answer-box {
                background-color: #F3F8F9;
                border-left: 4px solid var(--c-primary, #064E5A);
                border-radius: 10px;
                padding: 18px 22px;
                margin: 0 0 24px;
                color: #111827;
            }
            .lv-answer-box__title {
                color: var(--c-primary, #064E5A);
                font-weight: 700;
                margin-bottom: 8px;
                font-size: 1.25rem;
                line-height: 1.5;
            }
            .lv-answer-box__summary {
                margin: 0 0 16px;
                line-height: 1.6;
            }
            .lv-answer-box__points {
                padding-left: 20px;
                margin: 0 0 16px;
                list-style-type: disc;
                line-height: 1.6;
            }
            .lv-answer-box__points li {
                margin-bottom: 6px;
            }
            .lv-answer-box__points li:last-child {
                margin-bottom: 0;
            }
            .lv-answer-box__sources {
                margin: 0 0 12px;
                font-size: 0.9375rem;
                line-height: 1.6;
            }
            .lv-answer-box__sources a {
                color: var(--c-primary, #064E5A);
                text-decoration: underline;
                text-decoration-thickness: 1px;
                text-underline-offset: 2px;
            }
            .lv-answer-box__meta {
                font-size: 0.875rem;
                color: #6B7280;
                margin: 0;
                line-height: 1.5;
            }
            @media (max-width: 639px) {
                .lv-answer-box {
                    padding: 14px 16px;
                }
            }
        </style>
    @endonce
@endif
