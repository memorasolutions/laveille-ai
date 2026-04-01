@extends(fronttheme_layout())

@section('title', __('Actualités IA et technologie') . ' - ' . config('app.name'))
@section('meta_description', __('Veille quotidienne IA et technologie : résumés structurés par intelligence artificielle, classés par catégorie.'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Actualités')])
@endsection

@push('styles')
<style>
    .news-card { border: 1px solid #E5E7EB; border-radius: 12px; overflow: hidden; background: #fff; transition: transform 0.2s, box-shadow 0.2s; height: 100%; display: flex !important; flex-direction: column !important; }
    .news-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.08); border-color: var(--c-primary); }
    .news-card-img { width: 100%; height: 160px; object-fit: cover; }
    .news-card-gradient { width: 100%; height: 160px; display: flex !important; align-items: center !important; justify-content: center !important; }
    .news-card-body { padding: 16px; flex-grow: 1; display: flex !important; flex-direction: column !important; }
    .news-card-body h3 { margin: 0 0 8px; font-size: 1rem; font-weight: 700; font-family: var(--f-heading); line-height: 1.3; }
    .news-card-body h3 a { color: var(--c-dark); text-decoration: none; }
    .news-card-body h3 a:hover { color: var(--c-primary); }
    .news-tag { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; margin-bottom: 8px; }
    .news-score { font-size: 11px; font-weight: 700; padding: 2px 6px; border-radius: 10px; }
    .news-bullets { font-size: 13px; color: #4B5563; line-height: 1.5; margin: 8px 0; padding-left: 16px; }
    .news-bullets li { margin-bottom: 4px; }
    .news-why { font-size: 13px; color: #6B7280; line-height: 1.5; padding: 8px 12px; background: #f9fafb; border-radius: 8px; border-left: 3px solid var(--c-primary); margin: 8px 0; }
    .news-meta { font-size: 12px; color: #9CA3AF; padding-top: 10px; border-top: 1px solid #F3F4F6; margin-top: auto; }
    .news-section { margin-bottom: 32px; }
    .news-section-title { font-family: var(--f-heading); font-weight: 700; font-size: 1.3rem; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 2px solid var(--c-primary); display: flex !important; align-items: center !important; gap: 8px; }
</style>
@endpush

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <h1 style="font-family: var(--f-heading); margin-bottom: 4px;">{{ __('Actualités IA et technologie') }}</h1>
        <p style="color: #6B7280; margin-bottom: 24px;">{{ __('Veille quotidienne — résumés structurés par intelligence artificielle') }}</p>

        @if($grouped->isEmpty())
            <div style="text-align: center; padding: 60px 20px; color: #9CA3AF;">
                <div style="font-size: 3rem; margin-bottom: 12px;">📰</div>
                <p>{{ __('Aucune actualité pour le moment.') }}</p>
            </div>
        @else
            @foreach($grouped as $category => $articles)
            <div class="news-section">
                <h2 class="news-section-title">
                    <span>{{ $categoryIcons[$category] ?? '📰' }}</span>
                    {{ $category }}
                    <span style="font-size: 14px; font-weight: 400; color: #9CA3AF;">({{ $articles->count() }})</span>
                </h2>
                <div class="row">
                    @foreach($articles as $article)
                    <div class="col-md-6 col-lg-4" style="margin-bottom: 20px;">
                        <div class="news-card">
                            @if($article->image_url)
                                <img src="{{ $article->image_url }}" alt="{{ $article->seo_title ?? $article->title }}" class="news-card-img" loading="lazy">
                            @else
                                <div class="news-card-gradient" style="background: linear-gradient(135deg, {{ $article->impact_level === 'Élevé' ? '#0B7285' : ($article->impact_level === 'Moyen' ? '#6366f1' : '#9CA3AF') }}, var(--c-dark));">
                                    <span style="font-size: 2rem; color: #fff; opacity: 0.7;">{{ $categoryIcons[$category] ?? '📰' }}</span>
                                </div>
                            @endif
                            <div class="news-card-body">
                                {{-- Tags --}}
                                <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                                    @if($article->impact_level)
                                        <span class="news-tag" style="background: {{ $article->impact_level === 'Élevé' ? '#FEE2E2; color: #991B1B' : ($article->impact_level === 'Moyen' ? '#FEF3C7; color: #92400E' : '#F3F4F6; color: #6B7280') }};">
                                            ⚡ {{ $article->impact_level }}
                                        </span>
                                    @endif
                                    @if($article->relevance_score)
                                        <span class="news-score" style="background: {{ $article->relevance_score >= 8 ? '#D1FAE5; color: #065F46' : '#E0F2FE; color: #0369A1' }};">
                                            {{ $article->relevance_score }}/10
                                        </span>
                                    @endif
                                </div>

                                {{-- Titre --}}
                                <h3><a href="{{ route('news.show', $article) }}">{{ $article->seo_title ?? $article->title }}</a></h3>

                                {{-- Hook --}}
                                @php $ss = $article->structured_summary; @endphp
                                @if($ss && isset($ss['hook']))
                                    <p style="font-size: 14px; color: #374151; margin-bottom: 8px;">{{ $ss['hook'] }}</p>
                                @else
                                    <p style="font-size: 13px; color: #6B7280;">{{ Str::limit($article->summary ?? strip_tags($article->description), 120) }}</p>
                                @endif

                                {{-- Points clés --}}
                                @if($ss && !empty($ss['key_points']))
                                    <ul class="news-bullets">
                                        @foreach(array_slice($ss['key_points'], 0, 3) as $point)
                                            <li>{{ $point }}</li>
                                        @endforeach
                                    </ul>
                                @endif

                                {{-- Meta --}}
                                <div class="news-meta">
                                    <span style="background: #E5E7EB; padding: 2px 8px; border-radius: 4px; font-weight: 600; font-size: 11px;">{{ $article->source->name ?? __('Source') }}</span>
                                    <span style="float: right;">{{ $article->pub_date?->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        @endif
    </div>
</section>
@endsection
