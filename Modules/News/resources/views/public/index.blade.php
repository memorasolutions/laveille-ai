@extends(fronttheme_layout())

@section('title', __('Actualités IA et technologie') . ' - ' . config('app.name'))
@section('meta_description', __('Veille quotidienne IA et technologie : résumés structurés par intelligence artificielle, classés par catégorie.'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Actualités')])
@endsection

@push('styles')
<style>
    .nw-section { margin-bottom: 2.5rem; }
    .nw-section-title {
        font-family: var(--f-heading);
        font-size: 1.4rem;
        font-weight: 600;
        color: var(--c-dark);
        margin-bottom: 1.25rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #e5e7eb;
    }
    .nw-section-count { font-size: 0.875rem; font-weight: 400; color: #9ca3af; }
    .nw-card {
        display: flex;
        flex-direction: column;
        height: 100%;
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
        transition: box-shadow 0.2s ease;
    }
    .nw-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
    .nw-card-link { text-decoration: none; color: inherit; display: flex; flex-direction: column; height: 100%; }
    .nw-card-img-wrap {
        position: relative;
        overflow: hidden;
        aspect-ratio: 16 / 9;
        background: linear-gradient(135deg, #1a2332 0%, #0b7285 100%);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .nw-card-placeholder {
        color: rgba(255,255,255,0.3);
        font-size: 2.5rem;
        font-weight: 700;
        font-family: var(--f-heading);
        letter-spacing: 0.05em;
    }
    .nw-card-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease; }
    .nw-card:hover .nw-card-img { transform: scale(1.03); }
    .nw-card-body { padding: 1rem; flex: 1; display: flex; flex-direction: column; }
    .nw-card-title {
        font-family: var(--f-heading);
        font-size: 1.05rem;
        font-weight: 700;
        line-height: 1.35;
        color: var(--c-dark);
        margin: 0 0 0.5rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .nw-card-hook {
        font-size: 0.9rem;
        color: #4b5563;
        line-height: 1.55;
        margin-bottom: 0.75rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        flex: 1;
    }
    .nw-meta { display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; margin-top: auto; padding-top: 0.75rem; border-top: 1px solid #f3f4f6; }
    .nw-source-pill {
        background: #f3f4f6;
        color: #6b7280;
        font-size: 0.6875rem;
        font-weight: 600;
        padding: 0.15rem 0.5rem;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }
    .nw-date { font-size: 0.75rem; color: #9ca3af; display: flex; align-items: center; gap: 0.375rem; }
    .nw-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
    .nw-dot-high { background: #10b981; }
    .nw-dot-mid { background: #3b82f6; }
    .nw-dot-low { background: #d1d5db; }
    .nw-impact-bar { border-left: 3px solid transparent; }
    .nw-impact-high { border-left-color: #ef4444; }
    .nw-impact-mid { border-left-color: #f59e0b; }
    .nw-impact-low { border-left-color: #e5e7eb; }
    .nw-empty { text-align: center; padding: 4rem 1rem; color: #9ca3af; }
    .nw-page-intro { color: #6b7280; margin-bottom: 1.5rem; font-size: 1rem; }
</style>
@endpush

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <h1 style="font-family: var(--f-heading); margin-bottom: 0.25rem;">{{ __('Actualités IA et technologie') }}</h1>
        <p class="nw-page-intro">{{ __('Veille quotidienne — résumés structurés par intelligence artificielle') }}</p>

        @if($grouped->isEmpty())
            <div class="nw-empty">
                <p style="font-size: 1.125rem; margin-bottom: 0.5rem;">{{ __('Aucune actualité pour le moment.') }}</p>
                <p>{{ __('Revenez bientôt pour les dernières nouvelles.') }}</p>
            </div>
        @else
            @foreach($grouped as $category => $articles)
            <section class="nw-section">
                <h2 class="nw-section-title">
                    {{ $category }}
                    <span class="nw-section-count">({{ $articles->count() }})</span>
                </h2>
                <div class="row">
                    @foreach($articles as $article)
                    @php
                        $ss = $article->structured_summary;
                        $score = $article->relevance_score ?? 0;
                        $dotClass = $score >= 8 ? 'nw-dot-high' : ($score >= 6 ? 'nw-dot-mid' : 'nw-dot-low');
                        $impactClass = match($article->impact_level) { 'Élevé' => 'nw-impact-high', 'Moyen' => 'nw-impact-mid', default => 'nw-impact-low' };
                    @endphp
                    <div class="col-sm-6 col-md-4" style="margin-bottom: 1.25rem;">
                        <article class="nw-card nw-impact-bar {{ $impactClass }}">
                            <a href="{{ route('news.show', $article) }}" class="nw-card-link">
                                <div class="nw-card-img-wrap">
                                    @if($article->image_url)
                                        <img src="{{ $article->image_url }}" alt="" class="nw-card-img" loading="lazy">
                                    @else
                                        <span class="nw-card-placeholder">{{ mb_strtoupper(mb_substr($category, 0, 2)) }}</span>
                                    @endif
                                </div>
                                <div class="nw-card-body">
                                    <h3 class="nw-card-title">{{ $article->seo_title ?? $article->title }}</h3>
                                    <p class="nw-card-hook">
                                        @if($ss && isset($ss['hook']))
                                            {{ $ss['hook'] }}
                                        @else
                                            {{ Str::limit($article->summary ?? strip_tags($article->description), 120) }}
                                        @endif
                                    </p>
                                    <div class="nw-meta">
                                        <span class="nw-source-pill">{{ $article->source->name ?? __('Source') }}</span>
                                        <span class="nw-date">
                                            {{ $article->pub_date?->diffForHumans() }}
                                            @if($score)
                                                <span class="nw-dot {{ $dotClass }}" title="{{ $score }}/10"></span>
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </article>
                    </div>
                    @endforeach
                </div>
            </section>
            @endforeach
        @endif
    </div>
</section>
@endsection
