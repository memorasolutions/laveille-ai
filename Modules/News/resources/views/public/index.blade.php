@extends(fronttheme_layout())

@section('title', __('Actualités IA et technologie') . ' - ' . config('app.name'))
@section('meta_description', __('Veille quotidienne IA et technologie : résumés structurés, scorés par pertinence, pour professionnels et entreprises québécoises.'))

@push('styles')
<style>
    .news-card { border: 1px solid #E5E7EB; border-radius: 12px; overflow: hidden; background: #fff; transition: transform 0.2s, box-shadow 0.2s; margin-bottom: 16px; }
    .news-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); border-color: var(--c-primary); }
    .news-card-body { padding: 16px 20px; }
    .news-tag { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; color: #fff; margin-right: 6px; }
    .news-score { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 700; padding: 2px 8px; border-radius: 8px; }
    .news-hook { font-size: 15px; font-weight: 600; color: var(--c-dark); margin: 10px 0 8px; line-height: 1.4; }
    .news-bullet { font-size: 13px; color: #4b5563; line-height: 1.6; padding-left: 0; list-style: none; margin: 0 0 10px; }
    .news-bullet li { padding: 3px 0; }
    .news-bullet li::before { content: "→ "; color: var(--c-primary); font-weight: 700; }
    .news-why { font-size: 13px; color: #6B7280; line-height: 1.5; background: #f9fafb; padding: 10px 14px; border-radius: 8px; border-left: 3px solid var(--c-primary); margin-bottom: 10px; }
    .news-meta { font-size: 12px; color: #9CA3AF; display: flex; justify-content: space-between; align-items: center; padding-top: 10px; border-top: 1px solid #F3F4F6; }
    .news-section-header { display: flex; align-items: center; gap: 10px; margin: 28px 0 16px; padding-bottom: 10px; border-bottom: 2px solid #E5E7EB; }
    .news-section-header h2 { margin: 0; font-size: 1.2rem; font-family: var(--f-heading); font-weight: 700; }
    .news-section-count { background: var(--c-primary); color: #fff; padding: 2px 10px; border-radius: 12px; font-size: 12px; font-weight: 700; }
</style>
@endpush

@section('content')
<div class="container" style="padding: 30px 15px;">
    <h1 class="sr-only">{{ __('Actualités IA et technologie') }} — {{ config('app.name') }}</h1>

    <ol class="breadcrumb">
        <li><a href="{{ url('/') }}">{{ __('Accueil') }}</a></li>
        <li class="active">{{ __('Actualités') }}</li>
    </ol>

    <div style="margin-bottom: 24px;">
        <h2 style="font-family: var(--f-heading); margin: 0 0 4px; font-size: 1.6rem;">📅 {{ __('Veille du') }} {{ now()->translatedFormat('j F Y') }}</h2>
        <p style="color: #6B7280; margin: 0;">{{ __('Résumés IA quotidiens, scorés par pertinence') }}</p>
    </div>

    @php
        $categoryIcons = [
            'IA générative' => '🤖', 'Cybersécurité' => '🔒', 'Cloud' => '☁️',
            'Robotique' => '🦾', 'Données' => '📊', 'Startup' => '🚀',
            'Éducation tech' => '🎓', 'Développement' => '💻', 'Hardware' => '🖥️', 'Autre' => '📰',
        ];
        $categoryColors = [
            'IA générative' => '#8B5CF6', 'Cybersécurité' => '#DC2626', 'Cloud' => '#0891B2',
            'Robotique' => '#EA580C', 'Données' => '#059669', 'Startup' => '#D97706',
            'Éducation tech' => '#7C3AED', 'Développement' => '#2563EB', 'Hardware' => '#4B5563', 'Autre' => '#6B7280',
        ];
    @endphp

    @if(isset($grouped) && $grouped->isNotEmpty())
        @foreach($grouped as $category => $catArticles)
        <div class="news-section-header">
            <span style="font-size: 1.4rem;">{{ $categoryIcons[$category] ?? '📰' }}</span>
            <h2>{{ $category ?? __('Autre') }}</h2>
            <span class="news-section-count">{{ $catArticles->count() }}</span>
        </div>

        <div class="row">
            @foreach($catArticles as $article)
            @php $s = $article->structured_summary ?? []; @endphp
            <div class="col-md-6 col-sm-6 col-xs-12">
                <div class="news-card">
                    <div class="news-card-body">
                        {{-- Tags --}}
                        <div style="margin-bottom: 6px;">
                            <span class="news-tag" style="background: {{ $categoryColors[$category] ?? '#6B7280' }};">{{ $category ?? 'Autre' }}</span>
                            @if($article->impact_level)
                                <span class="news-tag" style="background: {{ $article->impact_level === 'Élevé' ? '#DC2626' : ($article->impact_level === 'Moyen' ? '#D97706' : '#6B7280') }};">⚡ {{ $article->impact_level }}</span>
                            @endif
                            @if($article->relevance_score)
                                <span class="news-score" style="background: {{ $article->relevance_score >= 8 ? '#d1fae5' : '#fef3c7' }}; color: {{ $article->relevance_score >= 8 ? '#065f46' : '#92400e' }};">📊 {{ $article->relevance_score }}/10</span>
                            @endif
                        </div>

                        {{-- Hook --}}
                        <div class="news-hook">
                            <a href="{{ route('news.show', $article) }}" style="color: inherit; text-decoration: none;">{{ $s['hook'] ?? $article->summary ?? $article->title }}</a>
                        </div>

                        {{-- Points clés --}}
                        @if(!empty($s['key_points']))
                        <ul class="news-bullet">
                            @foreach($s['key_points'] as $point)
                            <li>{{ $point }}</li>
                            @endforeach
                        </ul>
                        @endif

                        {{-- Pourquoi c'est important --}}
                        @if(!empty($s['why_important']))
                        <div class="news-why">
                            💡 {{ $s['why_important'] }}
                        </div>
                        @endif

                        {{-- Meta --}}
                        <div class="news-meta">
                            <span>{{ $article->source->name ?? __('Source') }} · {{ $article->pub_date?->diffForHumans() }}</span>
                            <a href="{{ $article->url }}" target="_blank" rel="noopener" style="color: var(--c-primary); font-weight: 600; text-decoration: none; font-size: 12px;">{{ __('Article original') }} →</a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endforeach
    @else
        {{-- Fallback : ancienne liste sans structured_summary --}}
        <div class="row">
            @forelse($articles as $article)
            <div class="col-md-6 col-sm-6 col-xs-12">
                <div class="news-card">
                    @if($article->image_url)
                        <img src="{{ $article->image_url }}" alt="{{ $article->title }}" style="width: 100%; height: 180px; object-fit: cover;" loading="lazy">
                    @endif
                    <div class="news-card-body">
                        <div class="news-hook">
                            <a href="{{ route('news.show', $article) }}" style="color: inherit; text-decoration: none;">{{ $article->title }}</a>
                        </div>
                        <p style="font-size: 13px; color: #6B7280; margin-bottom: 10px;">{{ Str::limit($article->summary ?? strip_tags($article->description), 150) }}</p>
                        <div class="news-meta">
                            <span>{{ $article->source->name ?? '' }} · {{ $article->pub_date?->diffForHumans() }}</span>
                            <a href="{{ $article->url }}" target="_blank" rel="noopener" style="color: var(--c-primary); font-weight: 600; text-decoration: none; font-size: 12px;">{{ __('Lire') }} →</a>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-xs-12" style="text-align: center; padding: 60px 20px; color: #9CA3AF;">
                <div style="font-size: 3rem; margin-bottom: 12px;">📰</div>
                <p>{{ __('Aucune actualité pour le moment.') }}</p>
            </div>
            @endforelse
        </div>
        @if(method_exists($articles, 'hasPages') && $articles->hasPages())
        <div style="text-align: center; margin-top: 20px;">{{ $articles->links() }}</div>
        @endif
    @endif
</div>
@endsection
