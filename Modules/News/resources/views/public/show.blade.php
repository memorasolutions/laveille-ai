@extends(fronttheme_layout())

@php $ss = $article->structured_summary; @endphp

@section('title', ($article->seo_title ?? $article->title) . ' - ' . __('Actualités') . ' - ' . config('app.name'))
@section('meta_description', $article->meta_description ?? Str::limit($article->summary ?? strip_tags($article->description), 155))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => Str::limit($article->seo_title ?? $article->title, 40),
        'breadcrumbItems' => [__('Actualités'), Str::limit($article->seo_title ?? $article->title, 40)]
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
    .news-show-img { width: 100%; max-height: 400px; object-fit: cover; border-radius: 12px; margin-bottom: 24px; }
    .news-structured { background: #fafbfc; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; margin-bottom: 24px; }
    .news-structured .ns-tags { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
    .news-structured .ns-tag { padding: 3px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; }
    .news-structured .ns-hook { font-size: 17px; font-weight: 600; color: var(--c-dark); line-height: 1.5; margin-bottom: 16px; font-family: var(--f-heading); }
    .news-structured .ns-bullets { padding-left: 20px; margin-bottom: 16px; }
    .news-structured .ns-bullets li { font-size: 15px; color: #374151; line-height: 1.6; margin-bottom: 6px; }
    .news-structured .ns-why { background: #fff; border-left: 3px solid var(--c-primary); padding: 14px 18px; border-radius: 0 8px 8px 0; margin-bottom: 16px; }
    .news-structured .ns-why p { font-size: 15px; color: #4B5563; line-height: 1.6; margin: 0; }
    .news-structured .ns-audience { font-size: 13px; color: #6B7280; }
    .news-faq { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 24px; }
</style>
@endpush

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <h1 style="font-family: var(--f-heading); margin-bottom: 12px;">{{ $article->seo_title ?? $article->title }}</h1>

                <div style="font-size: 13px; color: #6B7280; margin-bottom: 20px;">
                    <span style="background: #E5E7EB; padding: 2px 10px; border-radius: 4px; font-weight: 600; font-size: 12px;">{{ $article->source->name ?? __('Source') }}</span>
                    @if($article->author) · {{ $article->author }} @endif
                    · {{ $article->pub_date?->format('d/m/Y') }}
                    @if($article->category_tag)
                        · <span style="color: var(--c-primary); font-weight: 600;">{{ $article->category_tag }}</span>
                    @endif
                </div>

                @if($article->image_url)
                    <img src="{{ $article->image_url }}" alt="{{ $article->seo_title ?? $article->title }}" class="news-show-img" loading="lazy">
                @endif

                {{-- Résumé structuré --}}
                @if($ss && isset($ss['hook']))
                <div class="news-structured">
                    <div class="ns-tags">
                        @if($article->category_tag)
                            <span class="ns-tag" style="background: var(--c-primary-light, #E6F7F9); color: var(--c-primary);">🏷️ {{ $article->category_tag }}</span>
                        @endif
                        @if($article->impact_level)
                            <span class="ns-tag" style="background: {{ $article->impact_level === 'Élevé' ? '#FEE2E2' : ($article->impact_level === 'Moyen' ? '#FEF3C7' : '#F3F4F6') }}; color: {{ $article->impact_level === 'Élevé' ? '#991B1B' : ($article->impact_level === 'Moyen' ? '#92400E' : '#6B7280') }};">⚡ {{ $article->impact_level }}</span>
                        @endif
                        @if($article->relevance_score)
                            <span class="ns-tag" style="background: #D1FAE5; color: #065F46;">📊 {{ $article->relevance_score }}/10</span>
                        @endif
                    </div>

                    <div class="ns-hook">📌 {{ $ss['hook'] }}</div>

                    @if(!empty($ss['key_points']))
                    <h3 style="font-size: 14px; font-weight: 700; color: var(--c-dark); margin-bottom: 8px;">🔑 {{ __('Points clés') }}</h3>
                    <ul class="ns-bullets">
                        @foreach($ss['key_points'] as $point)
                            <li>{{ $point }}</li>
                        @endforeach
                    </ul>
                    @endif

                    @if(isset($ss['why_important']))
                    <h3 style="font-size: 14px; font-weight: 700; color: var(--c-dark); margin-bottom: 8px;">💡 {{ __('Pourquoi c\'est important') }}</h3>
                    <div class="ns-why"><p>{{ $ss['why_important'] }}</p></div>
                    @endif

                    @if(!empty($ss['audience']))
                    <div class="ns-audience">👥 {{ __('Qui est concerné') }} : {{ implode(', ', $ss['audience']) }}</div>
                    @endif
                </div>
                @elseif($article->summary)
                    <div style="background: #E6F7F9; border-left: 4px solid var(--c-primary); border-radius: 8px; padding: 16px 20px; margin-bottom: 24px;">
                        <strong style="display: block; margin-bottom: 8px; color: var(--c-primary); font-size: 14px;">⚡ {{ __('Résumé IA') }}</strong>
                        <p style="margin: 0; color: #1a365d; line-height: 1.6;">{{ $article->summary }}</p>
                    </div>
                @endif

                {{-- FAQ (SEO/AEO) --}}
                @if($ss && isset($ss['faq_question']))
                <div class="news-faq">
                    <h3 style="font-size: 16px; font-weight: 700; color: var(--c-dark); margin-bottom: 8px;">❓ {{ $ss['faq_question'] }}</h3>
                    <p style="font-size: 15px; color: #4B5563; line-height: 1.6; margin: 0;">{{ $ss['faq_answer'] }}</p>
                </div>
                @endif

                {{-- Description originale (seulement si pas de résumé structuré) --}}
                @if($article->description && !$ss)
                <div style="line-height: 1.7; color: var(--c-dark); margin-bottom: 30px;">
                    {!! nl2br(e($article->description)) !!}
                </div>
                @endif

                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{ $article->url }}" target="_blank" rel="noopener" style="display: inline-block; background: var(--c-primary); color: #fff; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-weight: 600;">{{ __('Voir l\'article original') }} →</a>
                </div>

                <div style="text-align: center;">
                    <a href="{{ route('news.index') }}" style="color: var(--c-primary); font-weight: 600; text-decoration: none;">← {{ __('Retour aux actualités') }}</a>
                </div>

            </div>
        </div>
    </div>
</section>
@endsection
