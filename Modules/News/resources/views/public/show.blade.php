@extends(fronttheme_layout())

@php $ss = $article->structured_summary; @endphp

@section('title', ($article->seo_title ?? $article->title) . ' - ' . __('Actualités') . ' - ' . config('app.name'))
@section('meta_description', $article->meta_description ?? Str::limit($article->summary ?? strip_tags($article->description), 155))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => $article->seo_title ?? $article->title,
        'breadcrumbItems' => [__('Actualités'), $article->seo_title ?? $article->title]
    ])
@endsection

{{-- Schema.org NewsArticle + FAQPage --}}
@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'NewsArticle',
    'headline' => $article->seo_title ?? $article->title,
    'description' => $article->meta_description ?? Str::limit($article->summary ?? '', 155),
    'image' => $article->image_url ?: asset('images/og-image.png'),
    'datePublished' => $article->pub_date?->toIso8601String(),
    'dateModified' => $article->updated_at->toIso8601String(),
    'author' => ['@type' => 'Organization', 'name' => $article->source->name ?? config('app.name')],
    'publisher' => ['@type' => 'Organization', 'name' => config('app.name'), 'logo' => ['@type' => 'ImageObject', 'url' => asset('images/favicon.png')]],
    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => route('news.show', $article)],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
@if($ss && isset($ss['faq_question']))
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => [['@type' => 'Question', 'name' => $ss['faq_question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $ss['faq_answer'] ?? '']]],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
@endif
@endpush

@push('styles')
<style>
    .nw-show { max-width: 740px; margin: 0 auto; }
    .nw-hero { width: 100%; max-height: 420px; object-fit: cover; border-radius: 12px; margin-bottom: 1.5rem; }
    .nw-show-title { font-family: var(--f-heading); font-size: 2rem; line-height: 1.2; margin-bottom: 1rem; }
    .nw-lead { font-size: 1.0625rem; font-weight: 600; color: var(--c-dark); line-height: 1.6; margin-bottom: 1.5rem; }
    .nw-meta-bar {
        display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;
        padding-bottom: 1rem; margin-bottom: 1.75rem; border-bottom: 1px solid #e5e7eb;
    }
    .nw-pill {
        display: inline-flex; align-items: center; gap: 0.25rem;
        padding: 0.2rem 0.625rem; border-radius: 4px;
        font-size: 0.8125rem; font-weight: 500; background: #f3f4f6; color: #374151;
    }
    .nw-pill-cat { background: var(--c-primary); color: #fff; }
    .nw-pill-sep { color: #d1d5db; font-size: 0.75rem; }
    .nw-section-heading {
        font-family: var(--f-heading); font-size: 1.125rem; font-weight: 700;
        color: var(--c-dark); margin-bottom: 0.75rem; padding-bottom: 0.375rem;
        border-bottom: 2px solid var(--c-primary);
    }
    .nw-key-list { padding-left: 1.25rem; margin-bottom: 1.75rem; }
    .nw-key-list li { font-size: 0.9375rem; color: #374151; line-height: 1.65; margin-bottom: 0.5rem; }
    .nw-why {
        border-left: 3px solid var(--c-primary); background: #f9fafb;
        padding: 1rem 1.25rem; border-radius: 0 8px 8px 0; margin-bottom: 1.75rem;
    }
    .nw-why p { font-size: 0.9375rem; color: #4b5563; line-height: 1.65; margin: 0; }
    .nw-faq { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.25rem; margin-bottom: 1.75rem; }
    .nw-faq h3 { font-family: var(--f-heading); font-size: 1rem; font-weight: 700; margin-bottom: 0.5rem; }
    .nw-faq p { font-size: 0.9375rem; color: #4b5563; line-height: 1.65; margin: 0; }
    .nw-audience { font-size: 0.8125rem; color: #6b7280; margin-bottom: 1.75rem; }
    .nw-desc { line-height: 1.7; color: var(--c-dark); margin-bottom: 2rem; }
    .nw-cta { display: inline-block; background: var(--c-primary); color: #fff; padding: 0.75rem 1.75rem; border-radius: 8px; text-decoration: none; font-weight: 600; }
    .nw-cta:hover { opacity: 0.9; color: #fff; text-decoration: none; }
    .nw-back { color: var(--c-primary); font-weight: 500; text-decoration: none; }
    .nw-back:hover { text-decoration: underline; }
    .nw-nav { border-top: 1px solid #e5e7eb; padding: 1.25rem 0; margin: 2rem 0 1rem; display: flex; justify-content: space-between; gap: 1rem; }
    .nw-nav a { color: var(--c-primary); text-decoration: none; font-size: 0.875rem; font-weight: 500; max-width: 48%; }
    .nw-nav a:hover { text-decoration: underline; }
    .nw-nav-next { text-align: right; margin-left: auto; }
    .nw-related { border-top: 1px solid #e5e7eb; padding-top: 1.5rem; margin-top: 1rem; }
    .nw-related h3 { font-family: var(--f-heading); font-size: 1.125rem; font-weight: 700; margin-bottom: 1rem; }
    .nw-related-grid { display: flex; flex-wrap: wrap; gap: 1rem; }
    .nw-related-card { flex: 1; min-width: 200px; max-width: 33%; }
    .nw-related-card a { text-decoration: none; color: inherit; }
    .nw-related-card a:hover .nw-related-title { color: var(--c-primary); }
    .nw-related-img { width: 100%; aspect-ratio: 16/9; object-fit: cover; border-radius: 6px; margin-bottom: 0.5rem; }
    .nw-related-title { font-family: var(--f-heading); font-size: 0.9rem; font-weight: 600; line-height: 1.35; margin-bottom: 0.375rem; color: var(--c-dark); }
    .nw-related-meta { font-size: 0.75rem; color: #9ca3af; }
    @media (max-width: 767px) { .nw-related-card { max-width: 100%; } }
    .nw-summary-fallback {
        background: #f0f9fa; border-left: 4px solid var(--c-primary);
        border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1.75rem;
    }
    .nw-summary-fallback p { margin: 0; color: #1a365d; line-height: 1.6; }
</style>
@endpush

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="nw-show">

                    <h1 class="nw-show-title">{{ $article->seo_title ?? $article->title }}</h1>

                    <div class="nw-meta-bar">
                        <span class="nw-pill">{{ $article->source->name ?? __('Source') }}</span>
                        @if($article->author)
                            <span class="nw-pill-sep">&middot;</span>
                            <span class="nw-pill">{{ $article->author }}</span>
                        @endif
                        <span class="nw-pill-sep">&middot;</span>
                        <span class="nw-pill">{{ $article->pub_date?->format('d/m/Y') }}</span>
                        @if($article->category_tag)
                            <span class="nw-pill nw-pill-cat">{{ $article->category_tag }}</span>
                        @endif
                        @if($article->relevance_score)
                            <span class="nw-pill">{{ $article->relevance_score }}/10</span>
                        @endif
                        @if($article->impact_level)
                            <span class="nw-pill">{{ $article->impact_level }}</span>
                        @endif
                    </div>

                    @if($article->image_url)
                        <img src="{{ $article->image_url }}" alt="{{ $article->seo_title ?? $article->title }}" class="nw-hero" loading="lazy">
                    @endif

                    {{-- Lead : hook IA --}}
                    @if($ss && isset($ss['hook']))
                        <p class="nw-lead">{{ $ss['hook'] }}</p>
                    @endif

                    {{-- Résumé structuré --}}
                    @if($ss)
                        @if(!empty($ss['key_points']))
                        <h3 class="nw-section-heading">{{ __('Points clés') }}</h3>
                        <ul class="nw-key-list">
                            @foreach($ss['key_points'] as $point)
                                <li>{{ $point }}</li>
                            @endforeach
                        </ul>
                        @endif

                        @if(isset($ss['why_important']))
                        <h3 class="nw-section-heading">{{ __('Pourquoi c\'est important') }}</h3>
                        <div class="nw-why"><p>{{ $ss['why_important'] }}</p></div>
                        @endif

                        @if(!empty($ss['audience']))
                        <p class="nw-audience">{{ __('Public concerné') }} : {{ implode(', ', $ss['audience']) }}</p>
                        @endif
                    @elseif($article->summary)
                        <div class="nw-summary-fallback">
                            <strong style="display: block; margin-bottom: 0.5rem; color: var(--c-primary); font-size: 0.875rem;">{{ __('Résumé IA') }}</strong>
                            <p>{{ $article->summary }}</p>
                        </div>
                    @endif

                    {{-- FAQ --}}
                    @if($ss && isset($ss['faq_question']))
                    <div class="nw-faq">
                        <h3>{{ $ss['faq_question'] }}</h3>
                        <p>{{ $ss['faq_answer'] }}</p>
                    </div>
                    @endif

                    {{-- Description originale (seulement si pas de résumé structuré) --}}
                    @if($article->description && !$ss)
                    <div class="nw-desc">
                        {!! nl2br(e($article->description)) !!}
                    </div>
                    @endif

                    <div style="text-align: center; margin: 2rem 0;">
                        <a href="{{ $article->url }}" target="_blank" rel="noopener" class="nw-cta">{{ __('Voir l\'article original') }} &rarr;</a>
                    </div>

                    {{-- Navigation précédent/suivant --}}
                    @if($previousArticle || $nextArticle)
                    <nav class="nw-nav">
                        @if($previousArticle)
                            <a href="{{ route('news.show', $previousArticle) }}">&larr; {{ Str::limit($previousArticle->seo_title ?? $previousArticle->title, 55) }}</a>
                        @else
                            <span></span>
                        @endif
                        @if($nextArticle)
                            <a href="{{ route('news.show', $nextArticle) }}" class="nw-nav-next">{{ Str::limit($nextArticle->seo_title ?? $nextArticle->title, 55) }} &rarr;</a>
                        @endif
                    </nav>
                    @endif

                    {{-- Articles connexes --}}
                    @if($relatedArticles->isNotEmpty())
                    <div class="nw-related">
                        <h3>{{ __('Articles connexes') }}</h3>
                        <div class="nw-related-grid">
                            @foreach($relatedArticles as $related)
                            <div class="nw-related-card">
                                <a href="{{ route('news.show', $related) }}">
                                    @if($related->image_url)
                                        <img src="{{ $related->image_url }}" alt="" class="nw-related-img" loading="lazy">
                                    @endif
                                    <div class="nw-related-title">{{ $related->seo_title ?? $related->title }}</div>
                                    <div class="nw-related-meta">{{ $related->source->name ?? '' }} &middot; {{ $related->pub_date?->diffForHumans() }}</div>
                                </a>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div style="text-align: center; margin-top: 1.5rem;">
                        <a href="{{ route('news.index') }}" class="nw-back">&larr; {{ __('Retour aux actualités') }}</a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>
@endsection
